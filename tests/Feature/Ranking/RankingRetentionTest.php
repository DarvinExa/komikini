<?php

declare(strict_types=1);

namespace Tests\Feature\Ranking;

use App\Enums\RankingPeriod;
use App\Models\Comic;
use App\Models\ComicRanking;
use App\Models\ComicViewEvent;
use App\Services\Ranking\RankingAggregationService;
use App\Services\Ranking\ViewEventRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RankingRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected RankingAggregationService $rankingService;

    protected ViewEventRetentionService $retentionService;

    protected Comic $comic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rankingService = app(RankingAggregationService::class);
        $this->retentionService = app(ViewEventRetentionService::class);

        $this->comic = Comic::create([
            'slug' => 'one-piece',
            'title' => 'One Piece',
            'comic_type' => 'manga',
        ]);
    }

    public function test_pruning_removes_old_events_and_preserves_all_time_aggregates(): void
    {
        $now = Carbon::parse('2026-09-29 12:00:00', 'UTC');

        // 1. Old event: 95 days ago (older than 90 days retention)
        ComicViewEvent::create([
            'comic_id' => $this->comic->id,
            'visitor_hash' => 'old-reader-1',
            'chapter_key' => 'ch-1',
            'occurred_at' => $now->copy()->subDays(95),
            'qualified' => true,
        ]);
        ComicViewEvent::create([
            'comic_id' => $this->comic->id,
            'visitor_hash' => 'old-reader-2',
            'chapter_key' => 'ch-1',
            'occurred_at' => $now->copy()->subDays(100),
            'qualified' => true,
        ]);

        // 2. Recent event: 10 days ago (within 90 days retention)
        ComicViewEvent::create([
            'comic_id' => $this->comic->id,
            'visitor_hash' => 'recent-reader-1',
            'chapter_key' => 'ch-2',
            'occurred_at' => $now->copy()->subDays(10),
            'qualified' => true,
        ]);

        $this->assertDatabaseCount('comic_view_events', 3);

        // Run retention pruning (90 days)
        $prunedCount = $this->retentionService->prune(90, $now);
        $this->assertEquals(2, $prunedCount);

        // Only 1 recent event remains in raw events table
        $this->assertDatabaseCount('comic_view_events', 1);

        // Baseline table should have captured the 2 pruned views and 2 unique readers
        $this->assertDatabaseHas('comic_ranking_baselines', [
            'comic_id' => $this->comic->id,
            'qualified_views' => 2,
            'unique_readers' => 2,
        ]);

        // Now aggregate all-time ranking: it must include both the pruned baseline AND the remaining raw event!
        $this->rankingService->aggregatePeriod(RankingPeriod::ALL_TIME, $now);

        $ranking = ComicRanking::forPeriod(RankingPeriod::ALL_TIME)->where('comic_id', $this->comic->id)->first();
        $this->assertNotNull($ranking);

        // Total qualified views: 2 (from baseline) + 1 (from raw event) = 3!
        $this->assertEquals(3, $ranking->qualified_views);
        // Total unique readers: 2 (from baseline) + 1 (from raw event) = 3!
        $this->assertEquals(3, $ranking->unique_readers);
    }
}
