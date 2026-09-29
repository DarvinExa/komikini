<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComicRankingBaseline extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'comic_id',
        'qualified_views',
        'unique_readers',
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
        ];
    }

    /**
     * Get the comic associated with this baseline.
     */
    public function comic(): BelongsTo
    {
        return $this->belongsTo(Comic::class);
    }
}
