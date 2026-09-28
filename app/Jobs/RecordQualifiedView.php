<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ComicViewEvent;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class RecordQualifiedView implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $comicId,
        public string $chapterKey,
        public ?int $userId = null,
        public ?string $visitorHash = null,
        public ?DateTimeInterface $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? Carbon::now();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $dedupHours = (int) config('comic.views.deduplication_hours', 6);
        $identifier = $this->userId !== null
            ? "user:{$this->userId}"
            : "visitor:{$this->visitorHash}";

        $cacheKey = "v1:view_dedup:{$this->comicId}:{$this->chapterKey}:{$identifier}";

        $isDuplicate = false;

        // 1. Fast path: check cache key
        if (Cache::has($cacheKey)) {
            $isDuplicate = true;
        } else {
            // 2. DB fallback: check if a qualified view exists within the deduplication window
            $windowStart = Carbon::parse($this->occurredAt)->subHours($dedupHours);

            $query = ComicViewEvent::query()
                ->where('comic_id', $this->comicId)
                ->where('chapter_key', $this->chapterKey)
                ->where('qualified', true)
                ->where('occurred_at', '>=', $windowStart);

            if ($this->userId !== null) {
                $query->where('user_id', $this->userId);
            } elseif ($this->visitorHash !== null) {
                $query->where('visitor_hash', $this->visitorHash);
            }

            if ($query->exists()) {
                $isDuplicate = true;
            }
        }

        $qualified = ! $isDuplicate;

        // Record view event
        ComicViewEvent::create([
            'comic_id' => $this->comicId,
            'user_id' => $this->userId,
            'visitor_hash' => $this->visitorHash,
            'chapter_key' => $this->chapterKey,
            'occurred_at' => $this->occurredAt,
            'qualified' => $qualified,
        ]);

        // If qualified, cache the view to deduplicate subsequent events quickly
        if ($qualified) {
            Cache::put($cacheKey, true, now()->addHours($dedupHours));
        }
    }
}
