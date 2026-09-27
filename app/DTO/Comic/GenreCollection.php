<?php

declare(strict_types=1);

namespace App\DTO\Comic;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, Genre>
 */
readonly class GenreCollection implements Countable, IteratorAggregate
{
    /**
     * @param  list<Genre>  $items
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
     * @return list<array<string, string>>
     */
    public function toArray(): array
    {
        return array_map(fn (Genre $genre): array => $genre->toArray(), $this->items);
    }

    /**
     * @param  list<array<string, mixed>>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            items: array_map(fn (array $itemData): Genre => Genre::fromArray($itemData), $data)
        );
    }
}
