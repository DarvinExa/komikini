<?php

declare(strict_types=1);

namespace App\DTO\Comic;

readonly class ChapterPayload
{
    /**
     * @param  list<string>  $images
     */
    public function __construct(
        public string $comicSlug,
        public string $chapterKey,
        public string $chapterNumber,
        public string $title,
        public array $images = [],
        public ?string $prevChapterKey = null,
        public ?string $nextChapterKey = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'comic_slug' => $this->comicSlug,
            'chapter_key' => $this->chapterKey,
            'chapter_number' => $this->chapterNumber,
            'title' => $this->title,
            'images' => $this->images,
            'prev_chapter_key' => $this->prevChapterKey,
            'next_chapter_key' => $this->nextChapterKey,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            comicSlug: (string) ($data['comic_slug'] ?? ''),
            chapterKey: (string) ($data['chapter_key'] ?? ''),
            chapterNumber: (string) ($data['chapter_number'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            images: array_values(array_map('strval', $data['images'] ?? [])),
            prevChapterKey: isset($data['prev_chapter_key']) ? (string) $data['prev_chapter_key'] : null,
            nextChapterKey: isset($data['next_chapter_key']) ? (string) $data['next_chapter_key'] : null,
        );
    }
}
