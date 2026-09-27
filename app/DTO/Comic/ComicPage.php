<?php

declare(strict_types=1);

namespace App\DTO\Comic;

readonly class ComicPage
{
    /**
     * @param  list<ComicItem>  $items
     */
    public function __construct(
        public array $items,
        public int $currentPage = 1,
        public bool $hasNextPage = false,
        public bool $hasPrevPage = false,
        public ?int $totalPages = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'items' => array_map(fn (ComicItem $item): array => $item->toArray(), $this->items),
            'current_page' => $this->currentPage,
            'has_next_page' => $this->hasNextPage,
            'has_prev_page' => $this->hasPrevPage,
            'total_pages' => $this->totalPages,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            items: array_map(fn (array $itemData): ComicItem => ComicItem::fromArray($itemData), $data['items'] ?? []),
            currentPage: (int) ($data['current_page'] ?? 1),
            hasNextPage: (bool) ($data['has_next_page'] ?? false),
            hasPrevPage: (bool) ($data['has_prev_page'] ?? false),
            totalPages: isset($data['total_pages']) ? (int) $data['total_pages'] : null,
        );
    }
}
