<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Ranking\RankingAggregationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AggregateComicRankingsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job with distributed lock protection.
     */
    public function handle(RankingAggregationService $service): void
    {
        $lock = Cache::lock('job:aggregate_comic_rankings', 120);

        if (! $lock->get()) {
            Log::info('AggregateComicRankingsJob dilewati: proses agregasi lain sedang berjalan.');

            return;
        }

        try {
            $results = $service->aggregateAll();
            Log::info('AggregateComicRankingsJob selesai berhasil.', $results);
        } finally {
            $lock->release();
        }
    }
}
