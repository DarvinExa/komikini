<?php

declare(strict_types=1);

namespace Tests\Feature\Ranking;

use App\Enums\RankingPeriod;
use App\Models\Comic;
use App\Models\ComicRanking;
use App\Models\ComicViewEvent;
use App\Services\Ranking\RankingAggregationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RankingPeriodAndBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected RankingAggregationService $service;

    protected Comic $comicA;

    protected Comic $comicB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RankingAggregationService::class);

        $this->comicA = Comic::create([
            'slug' => 'comic-a',
            'title' => 'Comic A',
            'comic_type' => 'manga',
        ]);

        $this->comicB = Comic::create([
            'slug' => 'comic-b',
            'title' => 'Comic B',
            'comic_type' => 'manhwa',
        ]);
    }

    public function test_daily_ranking_only_aggregates_events_within_24_hours(): void
    {
        $now = Carbon::parse('2026-09-29 12:00:00', 'UTC');

        // Comic A: 1 event 10 hours ago (inside 24h), 1 event 25 hours ago (outside 24h)
        ComicViewEvent::create([
            'comic_id' => $this->comicA->id,
            'visitor_hash' => 'vis-1',
            'chapter_key' => 'ch-1',
            'occurred_at' => $now->copy()->subHours(10),
            'qualified' => true,
        ]);
        ComicViewEvent::create([
            'comic_id' => $this->comicA->id,
            'visitor_hash' => 'vis-2',
            'chapter_key' => 'ch-1',
            'occurred_at' => $now->copy()->subHours(25),
            'qualified' => true,
        ]);

        // Comic B: 2 events inside 24 hours
        ComicViewEvent::create([
            'comic_id' => $this->comicB->id,
            'visitor_hash' => 'vis-3',
            'chapter_key' => 'ch-1',
            'occurred_at' => $now->copy()->subHours(2),
            'qualified' => true,
        ]);
        ComicViewEvent::create([
            'comic_id' => $this->comicB->id,
            'visitor_hash' => 'vis-4',
            'chapter_key' => 'ch-1',
            'occurred_at' => $now->copy()->subHours(5),
            'qualified' => true,
        ]);

        $this->service->aggregatePeriod(RankingPeriod::DAILY, $now);

        $rankings = ComicRanking::forPeriod(RankingPeriod::DAILY)->ordered()->get();

        $this->assertCount(2, $rankings);

        // Comic B should be rank 1 with 2 views
        $this->assertEquals($this->comicB->id, $rankings[0]->comic_id);
        $this->assertEquals(1, $rankings[0]->rank_position);
        $this->assertEquals(2, $rankings[0]->qualified_views);

        // Comic A should be rank 2 with 1 view (25h event excluded)
        $this->assertEquals($this->comicA->id, $rankings[1]->comic_id);
        $this->assertEquals(2, $rankings[1]->rank_position);
        $this->assertEquals(1, $rankings[1]->qualified_views);
    }

    public function test_unqualified_events_are_strictly_excluded_from_ranking(): void
    {
        $now = Carbon::parse('2026-09-29 12:00:00', 'UTC');

        // Comic A: 1 qualified view, 5 unqualified duplicate views
        ComicViewEvent::create([
            'comic_id' => $this->comicA->id,
            'visitor_hash' => 'vis-1',
            'chapter_key' => 'ch-1',
            'occurred_at' => $now->copy()->subHours(1),
            'qualified' => true,
        ]);

        for ($i = 0; $i < 5; $i++) {
            ComicViewEvent::create([
                'comic_id' => $this->comicA->id,
                'visitor_hash' => 'vis-1',
                'chapter_key' => 'ch-1',
                'occurred_at' => $now->copy()->subMinutes(10 * $i),
                'qualified' => false,
            ]);
        }

        $this->service->aggregatePeriod(RankingPeriod::DAILY, $now);

        $ranking = ComicRanking::forPeriod(RankingPeriod::DAILY)->where('comic_id', $this->comicA->id)->first();
        $this->assertNotNull($ranking);
        $this->assertEquals(1, $ranking->qualified_views);
    }

    public function test_weekly_and_monthly_cutoffs(): void
    {
        $now = Carbon::parse('2026-09-29 12:00:00', 'UTC');

        // Event 6 days ago (inside weekly and monthly)
        ComicViewEvent::create([
            'comic_id' => $this->comicA->id,
            'visitor_hash' => 'vis-1',
            'chapter_key' => 'ch-1',
            'occurred_at' => $now->copy()->subDays(6),
            'qualified' => true,
        ]);

        // Event 15 days ago (outside weekly, inside monthly)
        ComicViewEvent::create([
            'comic_id' => $this->comicA->id,
            'visitor_hash' => 'vis-2',
            'chapter_key' => 'ch-1',
            'occurred_at' => $now->copy()->subDays(15),
            'qualified' => true,
        ]);

        // Event 45 days ago (outside monthly)
        ComicViewEvent::create([
            'comic_id' => $this->comicA->id,
            'visitor_hash' => 'vis-3',
            'chapter_key' => 'ch-1',
            'occurred_at' => $now->copy()->subDays(45),
            'qualified' => true,
        ]);

        $this->service->aggregateAll($now);

        $weekly = ComicRanking::forPeriod(RankingPeriod::WEEKLY)->where('comic_id', $this->comicA->id)->first();
        $this->assertEquals(1, $weekly->qualified_views);

        $monthly = ComicRanking::forPeriod(RankingPeriod::MONTHLY)->where('comic_id', $this->comicA->id)->first();
        $this->assertEquals(2, $monthly->qualified_views);

        $allTime = ComicRanking::forPeriod(RankingPeriod::ALL_TIME)->where('comic_id', $this->comicA->id)->first();
        $this->assertEquals(3, $allTime->qualified_views);
    }
}
