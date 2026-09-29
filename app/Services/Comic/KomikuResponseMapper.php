<?php

declare(strict_types=1);

namespace App\Services\Comic;

use App\DTO\Comic\ChapterItem;
use App\DTO\Comic\ChapterPayload;
use App\DTO\Comic\ComicCollection;
use App\DTO\Comic\ComicDetail;
use App\DTO\Comic\ComicItem;
use App\DTO\Comic\ComicPage;
use App\DTO\Comic\Genre;
use App\DTO\Comic\GenreCollection;
use App\Enums\ComicType;
use App\Exceptions\ComicProvider\MalformedUpstreamResponseException;

class KomikuResponseMapper
{
    public function __construct(
        protected ImageUrlValidator $imageUrlValidator
    ) {}

    /**
     * Map a raw comic item array into ComicItem DTO.
     *
     * @param  array<string, mixed>  $data
     */
    public function mapComicItem(array $data): ComicItem
    {
        $rawTitle = (string) ($data['title'] ?? $data['name'] ?? $data['comic_name'] ?? '');
        $title = trim(strip_tags($rawTitle));

        if ($title === '') {
            throw new MalformedUpstreamResponseException('Item komik tidak memiliki judul yang valid.', context: ['data' => $data]);
        }

        $rawSlug = (string) ($data['slug'] ?? $data['endpoint'] ?? $data['param'] ?? '');
        $slug = $this->sanitizeSlug($rawSlug);

        if ($slug === '') {
            throw new MalformedUpstreamResponseException("Item komik '{$title}' tidak memiliki slug/endpoint yang valid.", context: ['data' => $data]);
        }

        $rawThumb = (string) ($data['thumbnail'] ?? $data['thumbnail_url'] ?? $data['image'] ?? $data['cover'] ?? '');
        $thumbnail = $this->imageUrlValidator->sanitize($rawThumb);

        $comicType = ComicType::fromUpstream(
            isset($data['type']) ? (string) $data['type'] : ($data['comic_type'] ?? null)
        );

        $rawLatest = (string) ($data['latest_chapter'] ?? $data['chapter'] ?? $data['latest'] ?? '');
        $latestChapter = $rawLatest !== '' ? trim(strip_tags($rawLatest)) : null;

        $rawRating = (string) ($data['rating'] ?? $data['score'] ?? '');
        $rating = $rawRating !== '' ? trim(strip_tags($rawRating)) : null;

        $rawDesc = (string) ($data['description'] ?? $data['desc'] ?? $data['synopsis'] ?? '');
        $description = $rawDesc !== '' ? trim(strip_tags($rawDesc)) : null;

        $rawTime = (string) ($data['relative_time'] ?? $data['uploaded_at'] ?? $data['time'] ?? '');
        $relativeTime = $rawTime !== '' ? trim(strip_tags($rawTime)) : null;

        return new ComicItem(
            slug: $slug,
            title: $title,
            thumbnailUrl: $thumbnail,
            comicType: $comicType,
            latestChapter: $latestChapter,
            rating: $rating,
            description: $description,
            relativeTime: $relativeTime,
        );
    }

    /**
     * Map raw array of items into ComicCollection.
     *
     * @param  array<string, mixed>|list<array<string, mixed>>  $data
     */
    public function mapComicCollection(array $data): ComicCollection
    {
        $rawItems = $this->extractItemsArray($data);

        $items = [];
        foreach ($rawItems as $itemData) {
            if (is_array($itemData)) {
                try {
                    $items[] = $this->mapComicItem($itemData);
                } catch (MalformedUpstreamResponseException) {
                    // Tolerant to individual broken items: skip broken items without failing the entire collection
                    continue;
                }
            }
        }

        return new ComicCollection($items);
    }

    /**
     * Map paginated raw data into ComicPage.
     *
     * @param  array<string, mixed>|list<array<string, mixed>>  $data
     */
    public function mapComicPage(array $data, int $page): ComicPage
    {
        $rawItems = $this->extractItemsArray($data);

        $items = [];
        foreach ($rawItems as $itemData) {
            if (is_array($itemData)) {
                try {
                    $items[] = $this->mapComicItem($itemData);
                } catch (MalformedUpstreamResponseException) {
                    continue;
                }
            }
        }

        $hasNextPage = (bool) ($data['has_next_page'] ?? $data['hasNextPage'] ?? (count($items) >= 24));
        $hasPrevPage = (bool) ($data['has_prev_page'] ?? $data['hasPrevPage'] ?? ($page > 1));
        $totalPages = isset($data['total_pages']) ? (int) $data['total_pages'] : null;

        return new ComicPage(
            items: $items,
            currentPage: $page,
            hasNextPage: $hasNextPage,
            hasPrevPage: $hasPrevPage,
            totalPages: $totalPages,
        );
    }

    /**
     * Map raw genre list into GenreCollection.
     *
     * @param  array<string, mixed>|list<mixed>  $data
     */
    public function mapGenres(array $data): GenreCollection
    {
        $rawItems = $this->extractItemsArray($data);

        $genres = [];
        foreach ($rawItems as $item) {
            if (is_array($item)) {
                $name = trim(strip_tags((string) ($item['name'] ?? $item['title'] ?? '')));
                $slug = $this->sanitizeSlug((string) ($item['slug'] ?? $item['endpoint'] ?? $item['param'] ?? ''));

                if ($name !== '' && $slug !== '') {
                    $genres[] = new Genre($slug, $name);
                }
            } elseif (is_string($item)) {
                $name = trim(strip_tags($item));
                $slug = $this->sanitizeSlug($name);
                if ($name !== '' && $slug !== '') {
                    $genres[] = new Genre($slug, $name);
                }
            }
        }

        return new GenreCollection($genres);
    }

    /**
     * Map raw comic detail into ComicDetail DTO.
     *
     * @param  array<string, mixed>  $data
     */
    public function mapComicDetail(array $data, string $slug): ComicDetail
    {
        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        $rawTitle = (string) ($data['title'] ?? $data['name'] ?? '');
        $title = trim(strip_tags($rawTitle));

        if ($title === '') {
            throw new MalformedUpstreamResponseException('Detail komik tidak memiliki judul yang valid.', context: ['data' => $data]);
        }

        $alternativeTitle = isset($data['alternative_title']) || isset($data['alter_title'])
            ? trim(strip_tags((string) ($data['alternative_title'] ?? $data['alter_title'] ?? '')))
            : null;

        $rawThumb = (string) ($data['thumbnail'] ?? $data['thumbnail_url'] ?? $data['image'] ?? '');
        $thumbnail = $this->imageUrlValidator->sanitize($rawThumb);

        $comicType = ComicType::fromUpstream(
            isset($data['type']) ? (string) $data['type'] : ($data['comic_type'] ?? null)
        );

        $status = isset($data['status']) ? trim(strip_tags((string) $data['status'])) : null;
        $author = isset($data['author']) ? trim(strip_tags((string) $data['author'])) : null;
        $synopsis = isset($data['synopsis']) ? trim(strip_tags((string) $data['synopsis'])) : null;

        // Genres
        $rawGenres = $data['genres'] ?? $data['genre'] ?? $data['genre_list'] ?? [];
        $genreCollection = $this->mapGenres(is_array($rawGenres) ? $rawGenres : []);

        // Chapters
        $rawChapters = $data['chapters'] ?? $data['chapter_list'] ?? $data['chapter'] ?? [];
        $chapters = [];

        if (is_array($rawChapters)) {
            foreach ($rawChapters as $cData) {
                if (is_array($cData)) {
                    $cTitle = trim(strip_tags((string) ($cData['title'] ?? $cData['name'] ?? '')));
                    $cKey = $this->sanitizeSlug((string) ($cData['chapter_key'] ?? $cData['slug'] ?? $cData['endpoint'] ?? $cData['param'] ?? $cTitle));

                    $cNumber = isset($cData['chapter_number'])
                        ? (string) $cData['chapter_number']
                        : $this->extractChapterNumber($cTitle, $cKey);

                    $cSlug = isset($cData['slug']) ? $this->sanitizeSlug((string) $cData['slug']) : null;
                    $cDate = isset($cData['release_date']) || isset($cData['date'])
                        ? trim(strip_tags((string) ($cData['release_date'] ?? $cData['date'] ?? '')))
                        : null;

                    if ($cKey !== '') {
                        $chapters[] = new ChapterItem(
                            chapterKey: $cKey,
                            chapterNumber: $cNumber,
                            title: $cTitle ?: "Chapter {$cNumber}",
                            slug: $cSlug,
                            releaseDate: $cDate ?: null,
                        );
                    }
                }
            }
        }

        $firstChapter = ! empty($chapters) ? end($chapters) : null;
        $latestChapter = ! empty($chapters) ? reset($chapters) : null;

        return new ComicDetail(
            slug: $slug,
            title: $title,
            alternativeTitle: $alternativeTitle ?: null,
            thumbnailUrl: $thumbnail,
            comicType: $comicType,
            publicationStatus: $status ?: null,
            author: $author ?: null,
            synopsis: $synopsis ?: null,
            genres: $genreCollection->items,
            chapters: $chapters,
            firstChapter: $firstChapter instanceof ChapterItem ? $firstChapter : null,
            latestChapter: $latestChapter instanceof ChapterItem ? $latestChapter : null,
        );
    }

    /**
     * Map raw chapter read response into ChapterPayload DTO.
     *
     * @param  array<string, mixed>  $data
     */
    public function mapChapterPayload(array $data, string $comicSlug, string $chapterKey): ChapterPayload
    {
        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        $rawTitle = (string) ($data['title'] ?? $data['chapter_name'] ?? '');
        $title = trim(strip_tags($rawTitle)) ?: "Chapter {$chapterKey}";

        $rawImages = $data['images'] ?? $data['image'] ?? $data['pages'] ?? [];
        if (! is_array($rawImages)) {
            $rawImages = [];
        }

        $images = [];
        foreach ($rawImages as $img) {
            $url = is_array($img) ? (string) ($img['url'] ?? $img['src'] ?? '') : (string) $img;
            $sanitized = $this->imageUrlValidator->sanitize($url);
            if ($sanitized !== null) {
                $images[] = $sanitized;
            }
        }

        $prevKey = isset($data['prev_chapter_key']) || isset($data['prev_chapter']) || isset($data['prev'])
            ? $this->sanitizeSlug((string) ($data['prev_chapter_key'] ?? $data['prev_chapter'] ?? $data['prev'] ?? ''))
            : null;

        $nextKey = isset($data['next_chapter_key']) || isset($data['next_chapter']) || isset($data['next'])
            ? $this->sanitizeSlug((string) ($data['next_chapter_key'] ?? $data['next_chapter'] ?? $data['next'] ?? ''))
            : null;

        $chapterNumber = isset($data['chapter_number'])
            ? (string) $data['chapter_number']
            : $this->extractChapterNumber($title, $chapterKey);

        return new ChapterPayload(
            comicSlug: $comicSlug,
            chapterKey: $chapterKey,
            chapterNumber: $chapterNumber,
            title: $title,
            images: $images,
            prevChapterKey: $prevKey !== '' ? $prevKey : null,
            nextChapterKey: $nextKey !== '' ? $nextKey : null,
        );
    }

    /**
     * Sanitize endpoint/param/slug to extract pure URL slug.
     */
    protected function sanitizeSlug(string $slug): string
    {
        $slug = trim($slug);
        $slug = trim($slug, '/');

        // Remove prefix like 'manga/', 'manhwa/', 'manhua/', 'komik/', 'ch/'
        $slug = (string) preg_replace('#^(manga|manhwa|manhua|komik|ch)/#i', '', $slug);

        return trim($slug);
    }

    /**
     * Extract chapter number string from title or key.
     */
    protected function extractChapterNumber(string $title, string $key): string
    {
        if (preg_match('/(?:ch(?:apter)?\.?\s*|#)([0-9]+(?:\.[0-9]+)?)/i', $title, $matches)) {
            return $matches[1];
        }

        if (preg_match('/chapter-([0-9]+(?:\.[0-9]+)?)/i', $key, $matches)) {
            return $matches[1];
        }

        return $key;
    }

    /**
     * Extract items array from wrapper data if needed.
     *
     * @param  array<string, mixed>|list<mixed>  $data
     * @return list<mixed>
     */
    protected function extractItemsArray(array $data): array
    {
        if (isset($data['data']) && is_array($data['data'])) {
            return array_values($data['data']);
        }

        if (isset($data['komik']) && is_array($data['komik'])) {
            return array_values($data['komik']);
        }

        if (isset($data['results']) && is_array($data['results'])) {
            return array_values($data['results']);
        }

        return array_values($data);
    }
}
