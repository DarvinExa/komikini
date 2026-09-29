<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RankingPeriod;
use App\Models\ComicRanking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ComicRankingController extends Controller
{
    /**
     * Display the internal popularity rankings.
     */
    public function index(Request $request): Response
    {
        $periodInput = (string) $request->query('period', RankingPeriod::DAILY->value);
        $period = RankingPeriod::tryFrom($periodInput) ?? RankingPeriod::DAILY;

        $cacheKey = "v1:rankings:view:{$period->value}";
        $freshTtl = (int) config('comic.cache.ttl.ranking.fresh', 900);

        $data = Cache::remember($cacheKey, $freshTtl, function () use ($period): array {
            $rankings = ComicRanking::query()
                ->forPeriod($period)
                ->ordered()
                ->with(['comic.genres'])
                ->get();

            $calculatedAt = $rankings->first()?->calculated_at;

            $items = $rankings->map(function (ComicRanking $ranking): array {
                $comic = $ranking->comic;

                return [
                    'rank_position' => $ranking->rank_position,
                    'qualified_views' => $ranking->qualified_views,
                    'unique_readers' => $ranking->unique_readers,
                    'comic' => [
                        'slug' => $comic->slug,
                        'title' => $comic->title,
                        'thumbnail_url' => $comic->thumbnail_url,
                        'comic_type' => $comic->comic_type,
                        'synopsis' => $comic->synopsis,
                        'genres' => $comic->genres->map(fn ($g) => [
                            'name' => $g->name,
                            'slug' => $g->slug,
                        ])->values()->all(),
                    ],
                ];
            })->values()->all();

            return [
                'items' => $items,
                'calculated_at' => $calculatedAt ? Carbon::parse($calculatedAt)->toISOString() : null,
            ];
        });

        $periods = array_map(fn (RankingPeriod $p): array => [
            'key' => $p->value,
            'label' => $p->label(),
        ], RankingPeriod::cases());

        return Inertia::render('Comic/Ranking', [
            'activePeriod' => $period->value,
            'periods' => $periods,
            'items' => $data['items'],
            'calculatedAt' => $data['calculated_at'],
        ]);
    }
}
