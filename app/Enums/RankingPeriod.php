<?php

declare(strict_types=1);

namespace App\Enums;

enum RankingPeriod: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case ALL_TIME = 'all_time';

    /**
     * Get all period values as array.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    /**
     * Get human readable label for UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'Harian',
            self::WEEKLY => 'Mingguan',
            self::MONTHLY => 'Bulanan',
            self::ALL_TIME => 'Sepanjang Waktu',
        };
    }
}
