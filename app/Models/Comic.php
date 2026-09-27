<?php

declare(strict_types=1);

namespace App\Models;

use App\DTO\Comic\ComicDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comic extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'alternative_title',
        'thumbnail_url',
        'comic_type',
        'publication_status',
        'synopsis',
        'upstream_payload',
        'last_synced_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'upstream_payload' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * Get the genres associated with the comic.
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'comic_genre');
    }

    /**
     * Get the comments for the comic.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Upsert minimum metadata from ComicDetail DTO into local database.
     */
    public static function upsertFromDetail(ComicDetail $detail): self
    {
        /** @var self $comic */
        $comic = static::updateOrCreate(
            ['slug' => $detail->slug],
            [
                'title' => $detail->title,
                'alternative_title' => $detail->alternativeTitle,
                'thumbnail_url' => $detail->thumbnailUrl,
                'comic_type' => $detail->comicType->value,
                'publication_status' => $detail->publicationStatus,
                'synopsis' => $detail->synopsis,
                'last_synced_at' => now(),
            ]
        );

        if (! empty($detail->genres)) {
            $genreIds = [];
            foreach ($detail->genres as $genreDto) {
                $genre = Genre::firstOrCreate(
                    ['slug' => $genreDto->slug],
                    ['name' => $genreDto->name]
                );
                $genreIds[] = $genre->id;
            }

            $comic->genres()->sync($genreIds);
        }

        return $comic;
    }
}
