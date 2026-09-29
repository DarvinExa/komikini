<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\ComicProviderInterface;
use App\Enums\ComicType;
use App\Enums\RankingPeriod;
use App\Exceptions\ComicProvider\ComicProviderException;
use App\Models\ComicRanking;
use App\Models\ReadingHistory;
use App\Services\Comic\ComicEnricher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        protected ComicProviderInterface $comicProvider
    ) {}

    /**
     * Display the Komikini discovery homepage.
     */
    public function __invoke(Request $request): Response
    {
        $error = null;
        $recommended = [];
        $popular = [];
        $latest = null;
        $genres = [];
        $manhwaChoices = [];

        try {
            $recommended = $this->comicProvider->recommended()->toArray();
            $popular = $this->comicProvider->popular()->toArray();
            $latest = $this->comicProvider->latest(1)->toArray();
            $genres = $this->comicProvider->genres()->toArray();
        } catch (ComicProviderException $e) {
            Log::warning("Gagal mengambil data katalog homepage: {$e->getMessage()}", $e->context());
            $error = 'Sebagian konten upstream sedang mengalami gangguan. Kami menyajikan konten yang tersedia.';
        }

        // Retrieve manhwa choices
        try {
            $manhwaList = $this->comicProvider->popular(ComicType::MANHWA)->toArray();
            if (! empty($manhwaList)) {
                $manhwaChoices = array_slice($manhwaList, 0, 6);
            }
        } catch (\Throwable) {
            // Graceful fallback below
        }

        if (empty($manhwaChoices)) {
            $candidates = array_merge($popular, $latest['items'] ?? []);
            $filtered = array_filter(
                $candidates,
                fn ($c) => strtolower((string) ($c['comic_type'] ?? '')) === 'manhwa'
            );
            $manhwaChoices = array_slice(array_values($filtered), 0, 6);
        }

        if (empty($manhwaChoices)) {
            $manhwaChoices = array_slice($popular, 0, 6);
        }

        // Enrich catalog comics with explicit chapter numbers and relative upload times
        if ($latest && isset($latest['items'])) {
            $latest['items'] = ComicEnricher::enrichList($latest['items']);
        }

        $recommended = ComicEnricher::enrichList($recommended);
        $popular = ComicEnricher::enrichList($popular);
        $manhwaChoices = ComicEnricher::enrichList($manhwaChoices);

        // Curated popular genres for homepage
        $popularSlugs = ['action', 'fantasy', 'romance', 'drama', 'comedy'];
        $curatedGenres = [];
        foreach ($popularSlugs as $slug) {
            foreach ($genres as $g) {
                if (strtolower((string) ($g['slug'] ?? '')) === $slug) {
                    $curatedGenres[] = [
                        'name' => preg_replace('/\s*\(\d+[\d.,]*\)/', '', (string) $g['name']),
                        'slug' => (string) $g['slug'],
                    ];
                    break;
                }
            }
        }

        if (empty($curatedGenres)) {
            $curatedGenres = [
                ['name' => 'Action', 'slug' => 'action'],
                ['name' => 'Fantasy', 'slug' => 'fantasy'],
                ['name' => 'Romance', 'slug' => 'romance'],
                ['name' => 'Drama', 'slug' => 'drama'],
                ['name' => 'Comedy', 'slug' => 'comedy'],
            ];
        }

        // Continue reading history for authenticated user
        $continueReading = [];
        $user = $request->user();
        if ($user) {
            try {
                if (Schema::hasTable('reading_histories')) {
                    $continueReading = $user->readingHistories()
                        ->with('comic')
                        ->orderByDesc('read_at')
                        ->take(3)
                        ->get()
                        ->map(fn (ReadingHistory $h): array => [
                            'id' => $h->id,
                            'comic_id' => $h->comic_id,
                            'comic_slug' => $h->comic->slug ?? '',
                            'comic_title' => $h->comic->title ?? ucwords(str_replace('-', ' ', (string) $h->comic_id)),
                            'comic_thumbnail' => $h->comic->thumbnail_url ?? null,
                            'chapter_key' => $h->chapter_key,
                            'chapter_number' => $h->chapter_number,
                            'last_image_index' => $h->last_image_index,
                            'progress_percent' => (float) $h->progress_percent,
                            'read_at' => $h->read_at->toISOString(),
                        ])
                        ->values()
                        ->all();
                }
            } catch (\Throwable) {
                $continueReading = [];
            }
        }

        // Internal ranking data for periods (daily, weekly, monthly)
        $rankings = [
            'daily' => $this->getRankingsForPeriod(RankingPeriod::DAILY, $popular),
            'weekly' => $this->getRankingsForPeriod(RankingPeriod::WEEKLY, $popular),
            'monthly' => $this->getRankingsForPeriod(RankingPeriod::MONTHLY, $popular),
        ];

        return Inertia::render('Home', [
            'recommended' => $recommended,
            'popular' => $popular,
            'latest' => $latest,
            'genres' => $curatedGenres,
            'manhwaChoices' => $manhwaChoices,
            'continueReading' => $continueReading,
            'rankings' => $rankings,
            'errorMessage' => $error,
        ]);
    }

    /**
     * Get ranked comics for a specific period with graceful fallback to popular.
     *
     * @param  array<int, mixed>  $fallbackComics
     * @return array<int, array<string, mixed>>
     */
    protected function getRankingsForPeriod(RankingPeriod $period, array $fallbackComics): array
    {
        $items = [];

        try {
            if (Schema::hasTable('comic_rankings')) {
                $dbRankings = ComicRanking::query()
                    ->forPeriod($period)
                    ->ordered()
                    ->with(['comic.genres'])
                    ->take(5)
                    ->get();

                if ($dbRankings->isNotEmpty()) {
                    foreach ($dbRankings as $ranking) {
                        $comic = $ranking->comic;
                        $enriched = ComicEnricher::enrich([
                            'slug' => $comic->slug,
                            'title' => $comic->title,
                            'thumbnail_url' => $comic->thumbnail_url,
                            'latest_chapter' => $comic->upstream_payload['latest_chapter'] ?? null,
                            'relative_time' => $comic->upstream_payload['relative_time'] ?? null,
                            'comic_type' => $comic->comic_type,
                        ], count($items));

                        $items[] = [
                            'rank' => count($items) + 1,
                            'title' => $enriched['title'],
                            'slug' => $enriched['slug'],
                            'thumbnail_url' => $enriched['thumbnail_url'],
                            'latest_chapter' => $enriched['latest_chapter'],
                            'relative_time' => $enriched['relative_time'],
                            'comic_type' => $enriched['comic_type'],
                        ];
                    }
                }
            }
        } catch (\Throwable) {
            // Graceful fallback to catalogue below
        }

        // If fewer than 5 items, pad with fallback popular comics so ranks 1 to 5 are always complete
        if (count($items) < 5) {
            $existingSlugs = array_column($items, 'slug');
            foreach ($fallbackComics as $fallback) {
                if (count($items) >= 5) {
                    break;
                }
                if (in_array($fallback['slug'] ?? '', $existingSlugs, true)) {
                    continue;
                }

                $enriched = ComicEnricher::enrich($fallback, count($items));
                $items[] = [
                    'rank' => count($items) + 1,
                    'title' => $enriched['title'],
                    'slug' => $enriched['slug'],
                    'thumbnail_url' => $enriched['thumbnail_url'],
                    'latest_chapter' => $enriched['latest_chapter'],
                    'relative_time' => $enriched['relative_time'],
                    'comic_type' => $enriched['comic_type'] ?? 'manga',
                ];
                $existingSlugs[] = $fallback['slug'];
            }
        }

        return $items;
    }
}
