<?php

declare(strict_types=1);

namespace App\Services\Ranking;

use App\Enums\RankingPeriod;
use App\Models\ComicRanking;
use App\Models\ComicRankingBaseline;
use App\Models\ComicViewEvent;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RankingAggregationService
{
    /**
     * Aggregate rankings for all periods.
     *
     * @return array<string, int> Map of period value to number of ranked comics.
     */
    public function aggregateAll(?DateTimeInterface $now = null): array
    {
        $results = [];
        foreach (RankingPeriod::cases() as $period) {
            $results[$period->value] = $this->aggregatePeriod($period, $now);
        }

        return $results;
    }

    /**
     * Aggregate rankings for a single period.
     *
     * @return int Number of ranked comics saved.
     */
    public function aggregatePeriod(RankingPeriod|string $period, ?DateTimeInterface $now = null): int
    {
        $periodEnum = $period instanceof RankingPeriod ? $period : RankingPeriod::from($period);
        $referenceTime = $now ? Carbon::parse($now)->utc() : Carbon::now('UTC');
        $limit = (int) config('comic.ranking.limit', 50);

        if ($periodEnum === RankingPeriod::ALL_TIME) {
            $rankedComics = $this->aggregateAllTime($limit);
        } else {
            $cutoff = match ($periodEnum) {
                RankingPeriod::DAILY => $referenceTime->copy()->subHours(24),
                RankingPeriod::WEEKLY => $referenceTime->copy()->subDays(7),
                RankingPeriod::MONTHLY => $referenceTime->copy()->subDays(30),
            };

            $rankedComics = ComicViewEvent::query()
                ->where('qualified', true)
                ->where('occurred_at', '>=', $cutoff)
                ->where('occurred_at', '<=', $referenceTime)
                ->selectRaw('comic_id, COUNT(id) as qualified_views, COUNT(DISTINCT COALESCE(user_id, visitor_hash)) as unique_readers')
                ->groupBy('comic_id')
                ->orderByDesc('qualified_views')
                ->orderByDesc('unique_readers')
                ->orderBy('comic_id', 'asc')
                ->limit($limit)
                ->get();
        }

        DB::transaction(function () use ($periodEnum, $rankedComics, $referenceTime): void {
            // Delete existing rankings for this period atomically
            ComicRanking::where('period', $periodEnum->value)->delete();

            $records = [];
            $rank = 1;
            foreach ($rankedComics as $row) {
                $records[] = [
                    'comic_id' => $row->comic_id,
                    'period' => $periodEnum->value,
                    'qualified_views' => (int) $row->qualified_views,
                    'unique_readers' => (int) $row->unique_readers,
                    'rank_position' => $rank++,
                    'calculated_at' => $referenceTime,
                    'created_at' => $referenceTime,
                    'updated_at' => $referenceTime,
                ];
            }

            if (! empty($records)) {
                ComicRanking::insert($records);
            }
        });

        // Invalidate cached ranking for this period
        Cache::forget("v1:rankings:{$periodEnum->value}");

        return count($rankedComics);
    }

    /**
     * Compute all-time ranking merging unpruned raw events with historical pruned baselines.
     *
     * @return list<object{comic_id: int, qualified_views: int, unique_readers: int}>
     */
    protected function aggregateAllTime(int $limit): array
    {
        $eventTotals = ComicViewEvent::query()
            ->where('qualified', true)
            ->selectRaw('comic_id, COUNT(id) as qualified_views, COUNT(DISTINCT COALESCE(user_id, visitor_hash)) as unique_readers')
            ->groupBy('comic_id')
            ->get()
            ->keyBy('comic_id');

        $baselines = ComicRankingBaseline::all()->keyBy('comic_id');

        $allComicIds = $eventTotals->keys()->merge($baselines->keys())->unique();

        $combined = [];
        foreach ($allComicIds as $comicId) {
            $evViews = (int) ($eventTotals[$comicId]->qualified_views ?? 0);
            $evReaders = (int) ($eventTotals[$comicId]->unique_readers ?? 0);
            $baseViews = (int) ($baselines[$comicId]->qualified_views ?? 0);
            $baseReaders = (int) ($baselines[$comicId]->unique_readers ?? 0);

            $combined[] = (object) [
                'comic_id' => (int) $comicId,
                'qualified_views' => $evViews + $baseViews,
                'unique_readers' => $evReaders + $baseReaders,
            ];
        }

        // Deterministic Tie-Break:
        // 1. qualified_views DESC
        // 2. unique_readers DESC
        // 3. comic_id ASC
        usort($combined, function ($a, $b) {
            if ($b->qualified_views !== $a->qualified_views) {
                return $b->qualified_views <=> $a->qualified_views;
            }
            if ($b->unique_readers !== $a->unique_readers) {
                return $b->unique_readers <=> $a->unique_readers;
            }

            return $a->comic_id <=> $b->comic_id;
        });

        return array_slice($combined, 0, $limit);
    }
}
