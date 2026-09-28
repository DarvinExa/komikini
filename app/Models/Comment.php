<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Comment extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'user_id',
        'comic_id',
        'chapter_key',
        'parent_id',
        'body',
        'status',
        'edited_at',
        'moderated_by',
        'moderated_at',
        'moderation_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    /**
     * Activity log options for moderation audits.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'moderated_by', 'moderated_at', 'moderation_reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('comment');
    }

    /**
     * Boot model events.
     */
    protected static function booted(): void
    {
        static::creating(function (Comment $comment): void {
            if (empty($comment->public_id)) {
                $comment->public_id = (string) Str::ulid();
            }
        });
    }

    /**
     * Get the user who authored the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the comic on which the comment is posted.
     */
    public function comic(): BelongsTo
    {
        return $this->belongsTo(Comic::class);
    }

    /**
     * Get the parent comment if this is a reply.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get the single-level replies to this comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get the reactions for the comment.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(CommentReaction::class);
    }

    /**
     * Get the reports filed against this comment.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(CommentReport::class);
    }

    /**
     * Get the moderator who performed moderation on this comment.
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Check if this comment is a root comment (not a reply).
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Check if comment is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if comment is hidden by moderator.
     */
    public function isHidden(): bool
    {
        return $this->status === 'hidden';
    }

    /**
     * Check if comment is deleted.
     */
    public function isDeleted(): bool
    {
        return $this->status === 'deleted';
    }

    /**
     * Scope a query to only include published comments.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to only include root comments.
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
