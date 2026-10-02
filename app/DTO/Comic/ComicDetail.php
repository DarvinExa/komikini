<?php

declare(strict_types=1);

namespace App\DTO\Comic;

use App\Enums\ComicType;

readonly class ComicDetail
{
    /**
     * @param  list<Genre>  $genres
     * @param  list<ChapterItem>  $chapters
     */
    public function __construct(
        public string $slug,
        public string $title,
        public ?string $alternativeTitle = null,
        public ?string $thumbnailUrl = null,
        public ComicType $comicType = ComicType::UNKNOWN,
        public ?string $publicationStatus = null,
        public ?string $author = null,
        public ?string $synopsis = null,
        public array $genres = [],
        public array $chapters = [],
        public ?ChapterItem $firstChapter = null,
        public ?ChapterItem $latestChapter = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $thumb = $this->thumbnailUrl;
        if ($thumb !== null) {
            $thumb = str_replace('thumbnail.komiku.to', 'thumbnail.komiku.org', $thumb);
        }

        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'alternative_title' => $this->alternativeTitle,
            'thumbnail_url' => $thumb,
            'comic_type' => $this->comicType->value,
            'publication_status' => $this->publicationStatus,
            'author' => $this->author,
            'synopsis' => $this->synopsis,
            'genres' => array_map(fn (Genre $g): array => $g->toArray(), $this->genres),
            'chapters' => array_map(fn (ChapterItem $c): array => $c->toArray(), $this->chapters),
            'first_chapter' => $this->firstChapter?->toArray(),
            'latest_chapter' => $this->latestChapter?->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $genres = array_map(
            fn (array $genreData): Genre => Genre::fromArray($genreData),
            $data['genres'] ?? []
        );

        $chapters = array_map(
            fn (array $chapterData): ChapterItem => ChapterItem::fromArray($chapterData),
            $data['chapters'] ?? []
        );

        $thumb = isset($data['thumbnail_url']) ? (string) $data['thumbnail_url'] : null;
        if ($thumb !== null) {
            $thumb = str_replace('thumbnail.komiku.to', 'thumbnail.komiku.org', $thumb);
        }

        return new self(
            slug: (string) ($data['slug'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            alternativeTitle: isset($data['alternative_title']) ? (string) $data['alternative_title'] : null,
            thumbnailUrl: $thumb,
            comicType: ComicType::fromUpstream($data['comic_type'] ?? null),
            publicationStatus: isset($data['publication_status']) ? (string) $data['publication_status'] : null,
            author: isset($data['author']) ? (string) $data['author'] : null,
            synopsis: isset($data['synopsis']) ? (string) $data['synopsis'] : null,
            genres: $genres,
            chapters: $chapters,
            firstChapter: isset($data['first_chapter']) && is_array($data['first_chapter']) ? ChapterItem::fromArray($data['first_chapter']) : null,
            latestChapter: isset($data['latest_chapter']) && is_array($data['latest_chapter']) ? ChapterItem::fromArray($data['latest_chapter']) : null,
        );
    }
}
