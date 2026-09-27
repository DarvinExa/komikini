<?php

declare(strict_types=1);

namespace App\DTO\Comic;

readonly class ChapterItem
{
    public function __construct(
        public string $chapterKey,
        public string $chapterNumber,
        public string $title,
        public ?string $slug = null,
        public ?string $releaseDate = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'chapter_key' => $this->chapterKey,
            'chapter_number' => $this->chapterNumber,
            'title' => $this->title,
            'slug' => $this->slug,
            'release_date' => $this->releaseDate,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            chapterKey: (string) ($data['chapter_key'] ?? ''),
            chapterNumber: (string) ($data['chapter_number'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            slug: isset($data['slug']) ? (string) $data['slug'] : null,
            releaseDate: isset($data['release_date']) ? (string) $data['release_date'] : null,
        );
    }
}
