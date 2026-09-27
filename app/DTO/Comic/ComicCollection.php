<?php

declare(strict_types=1);

namespace App\DTO\Comic;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, ComicItem>
 */
readonly class ComicCollection implements Countable, IteratorAggregate
{
    /**
     * @param  list<ComicItem>  $items
     */
    public function __construct(
        public array $items = []
    ) {}

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(fn (ComicItem $item): array => $item->toArray(), $this->items);
    }

    /**
     * @param  list<array<string, mixed>>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            items: array_map(fn (array $itemData): ComicItem => ComicItem::fromArray($itemData), $data)
        );
    }
}
