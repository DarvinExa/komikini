<?php

declare(strict_types=1);

namespace App\Enums;

enum ComicType: string
{
    case MANGA = 'manga';
    case MANHWA = 'manhwa';
    case MANHUA = 'manhua';
    case UNKNOWN = 'unknown';

    /**
     * Map arbitrary upstream string to enum safely.
     */
    public static function fromUpstream(?string $value): self
    {
        if ($value === null) {
            return self::UNKNOWN;
        }

        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'manga' => self::MANGA,
            'manhwa' => self::MANHWA,
            'manhua' => self::MANHUA,
            default => self::UNKNOWN,
        };
    }
}
