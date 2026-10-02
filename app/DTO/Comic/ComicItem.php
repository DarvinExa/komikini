<?php

declare(strict_types=1);

namespace App\DTO\Comic;

use App\Enums\ComicType;

readonly class ComicItem
{
    public function __construct(
        public string $slug,
        public string $title,
        public ?string $thumbnailUrl = null,
        public ComicType $comicType = ComicType::UNKNOWN,
        public ?string $latestChapter = null,
        public ?string $rating = null,
        public ?string $description = null,
        public ?string $relativeTime = null,
    ) {}

    /**
     * Convert to array for serialization and Inertia props.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $thumbnail = $this->thumbnailUrl;
        if ($thumbnail !== null) {
            $thumbnail = str_replace('thumbnail.komiku.to', 'thumbnail.komiku.org', $thumbnail);
        }

        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'thumbnail_url' => $thumbnail,
            'comic_type' => $this->comicType->value,
            'latest_chapter' => $this->latestChapter,
            'rating' => $this->rating,
            'description' => $this->description,
            'relative_time' => $this->relativeTime,
        ];
    }

    /**
     * Instantiate from serialized array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $thumb = isset($data['thumbnail_url']) ? (string) $data['thumbnail_url'] : null;
        if ($thumb !== null) {
            $thumb = str_replace('thumbnail.komiku.to', 'thumbnail.komiku.org', $thumb);
        }

        return new self(
            slug: (string) ($data['slug'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            thumbnailUrl: $thumb,
            comicType: ComicType::fromUpstream($data['comic_type'] ?? null),
            latestChapter: isset($data['latest_chapter']) ? (string) $data['latest_chapter'] : null,
            rating: isset($data['rating']) ? (string) $data['rating'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            relativeTime: isset($data['relative_time']) ? (string) $data['relative_time'] : (isset($data['uploaded_at']) ? (string) $data['uploaded_at'] : null),
        );
    }
}
