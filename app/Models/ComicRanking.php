<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RankingPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComicRanking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'comic_id',
        'period',
        'qualified_views',
        'unique_readers',
        'rank_position',
        'calculated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qualified_views' => 'integer',
            'unique_readers' => 'integer',
            'rank_position' => 'integer',
            'calculated_at' => 'datetime',
        ];
    }

    /**
     * Get the comic associated with this ranking.
     */
    public function comic(): BelongsTo
    {
        return $this->belongsTo(Comic::class);
    }

    /**
     * Scope a query to only include rankings for a specific period.
     */
    public function scopeForPeriod(Builder $query, RankingPeriod|string $period): Builder
    {
        $periodValue = $period instanceof RankingPeriod ? $period->value : $period;

        return $query->where('period', $periodValue);
    }

    /**
     * Scope a query ordered by rank position.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('rank_position', 'asc');
    }
}
