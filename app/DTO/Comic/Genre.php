<?php

declare(strict_types=1);

namespace App\DTO\Comic;

readonly class Genre
{
    public function __construct(
        public string $slug,
        public string $name,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            slug: (string) ($data['slug'] ?? ''),
            name: (string) ($data['name'] ?? ''),
        );
    }
}
