<?php

declare(strict_types=1);

namespace App\Services\Ranking;

use App\Models\ComicRankingBaseline;
use App\Models\ComicViewEvent;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ViewEventRetentionService
{
    /**
     * Prune raw view events older than retention days while preserving all-time aggregates.
     *
     * @return int Number of pruned view event records.
     */
    public function prune(?int $retentionDays = null, ?DateTimeInterface $now = null): int
    {
        $days = $retentionDays ?? (int) config('comic.views.retention_days', 90);
        $referenceTime = $now ? Carbon::parse($now)->utc() : Carbon::now('UTC');
        $cutoff = $referenceTime->copy()->subDays($days);

        return DB::transaction(function () use ($cutoff): int {
            // 1. Calculate totals from qualified events that are about to be pruned
            $olderTotals = ComicViewEvent::query()
                ->where('qualified', true)
                ->where('occurred_at', '<', $cutoff)
                ->selectRaw('comic_id, COUNT(id) as qualified_views, COUNT(DISTINCT COALESCE(user_id, visitor_hash)) as unique_readers')
                ->groupBy('comic_id')
                ->get();

            // 2. Accumulate into ComicRankingBaseline so all-time rankings remain permanently accurate
            foreach ($olderTotals as $row) {
                /** @var ComicRankingBaseline $baseline */
                $baseline = ComicRankingBaseline::firstOrNew(['comic_id' => $row->comic_id]);
                $baseline->qualified_views = (int) $baseline->qualified_views + (int) $row->qualified_views;
                $baseline->unique_readers = (int) $baseline->unique_readers + (int) $row->unique_readers;
                $baseline->save();
            }

            // 3. Prune all raw events older than cutoff date
            return ComicViewEvent::query()
                ->where('occurred_at', '<', $cutoff)
                ->delete();
        });
    }
}
