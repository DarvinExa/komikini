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

class RankingTieBreakAndDeterminismTest extends TestCase
{
    use RefreshDatabase;

    protected RankingAggregationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RankingAggregationService::class);
    }

    public function test_deterministic_tie_break_rules(): void
    {
        $now = Carbon::parse('2026-09-29 12:00:00', 'UTC');

        // Create 3 comics
        $comic1 = Comic::create(['slug' => 'comic-1', 'title' => 'Comic 1']);
        $comic2 = Comic::create(['slug' => 'comic-2', 'title' => 'Comic 2']);
        $comic3 = Comic::create(['slug' => 'comic-3', 'title' => 'Comic 3']);

        // Scenario:
        // Comic 1 has 4 views by 2 unique readers
        for ($i = 0; $i < 2; $i++) {
            ComicViewEvent::create([
                'comic_id' => $comic1->id,
                'visitor_hash' => 'reader-a',
                'chapter_key' => 'ch-'.($i + 1),
                'occurred_at' => $now->copy()->subHours(1),
                'qualified' => true,
            ]);
            ComicViewEvent::create([
                'comic_id' => $comic1->id,
                'visitor_hash' => 'reader-b',
                'chapter_key' => 'ch-'.($i + 1),
                'occurred_at' => $now->copy()->subHours(1),
                'qualified' => true,
            ]);
        }

        // Comic 2 has 4 views by 4 unique readers (same views as Comic 1, but more unique readers)
        for ($i = 0; $i < 4; $i++) {
            ComicViewEvent::create([
                'comic_id' => $comic2->id,
                'visitor_hash' => 'unique-reader-'.$i,
                'chapter_key' => 'ch-1',
                'occurred_at' => $now->copy()->subHours(1),
                'qualified' => true,
            ]);
        }

        // Comic 3 has 5 views (most views)
        for ($i = 0; $i < 5; $i++) {
            ComicViewEvent::create([
                'comic_id' => $comic3->id,
                'visitor_hash' => 'reader-x',
                'chapter_key' => 'ch-'.($i + 1),
                'occurred_at' => $now->copy()->subHours(1),
                'qualified' => true,
            ]);
        }

        $this->service->aggregatePeriod(RankingPeriod::DAILY, $now);

        $rankings = ComicRanking::forPeriod(RankingPeriod::DAILY)->ordered()->get();

        $this->assertCount(3, $rankings);

        // Rank 1: Comic 3 (5 views)
        $this->assertEquals($comic3->id, $rankings[0]->comic_id);
        $this->assertEquals(1, $rankings[0]->rank_position);
        $this->assertEquals(5, $rankings[0]->qualified_views);

        // Rank 2: Comic 2 (4 views, 4 unique readers - wins over Comic 1)
        $this->assertEquals($comic2->id, $rankings[1]->comic_id);
        $this->assertEquals(2, $rankings[1]->rank_position);
        $this->assertEquals(4, $rankings[1]->qualified_views);
        $this->assertEquals(4, $rankings[1]->unique_readers);

        // Rank 3: Comic 1 (4 views, 2 unique readers)
        $this->assertEquals($comic1->id, $rankings[2]->comic_id);
        $this->assertEquals(3, $rankings[2]->rank_position);
        $this->assertEquals(4, $rankings[2]->qualified_views);
        $this->assertEquals(2, $rankings[2]->unique_readers);
    }

    public function test_identical_metrics_tie_broken_deterministically_by_comic_id(): void
    {
        $now = Carbon::parse('2026-09-29 12:00:00', 'UTC');

        $comicA = Comic::create(['slug' => 'alpha', 'title' => 'Alpha']);
        $comicB = Comic::create(['slug' => 'beta', 'title' => 'Beta']);

        // Both comics get exact same 2 qualified views and 2 unique readers
        foreach ([$comicA, $comicB] as $comic) {
            ComicViewEvent::create([
                'comic_id' => $comic->id,
                'visitor_hash' => 'r1',
                'chapter_key' => 'ch-1',
                'occurred_at' => $now->copy()->subHour(),
                'qualified' => true,
            ]);
            ComicViewEvent::create([
                'comic_id' => $comic->id,
                'visitor_hash' => 'r2',
                'chapter_key' => 'ch-1',
                'occurred_at' => $now->copy()->subHour(),
                'qualified' => true,
            ]);
        }

        $this->service->aggregatePeriod(RankingPeriod::DAILY, $now);

        $rankings = ComicRanking::forPeriod(RankingPeriod::DAILY)->ordered()->get();

        // The lower comic_id must strictly come first
        $expectedFirst = min($comicA->id, $comicB->id);
        $expectedSecond = max($comicA->id, $comicB->id);

        $this->assertEquals($expectedFirst, $rankings[0]->comic_id);
        $this->assertEquals(1, $rankings[0]->rank_position);

        $this->assertEquals($expectedSecond, $rankings[1]->comic_id);
        $this->assertEquals(2, $rankings[1]->rank_position);
    }

    public function test_aggregation_is_idempotent_and_does_not_duplicate_rows(): void
    {
        $now = Carbon::parse('2026-09-29 12:00:00', 'UTC');
        $comic = Comic::create(['slug' => 'comic-idem', 'title' => 'Comic Idem']);

        ComicViewEvent::create([
            'comic_id' => $comic->id,
            'visitor_hash' => 'user-1',
            'chapter_key' => 'ch-1',
            'occurred_at' => $now->copy()->subMinutes(30),
            'qualified' => true,
        ]);

        // Run 3 times
        $this->service->aggregatePeriod(RankingPeriod::DAILY, $now);
        $this->service->aggregatePeriod(RankingPeriod::DAILY, $now);
        $this->service->aggregatePeriod(RankingPeriod::DAILY, $now);

        $this->assertDatabaseCount('comic_rankings', 1);

        $ranking = ComicRanking::first();
        $this->assertEquals($comic->id, $ranking->comic_id);
        $this->assertEquals(1, $ranking->rank_position);
        $this->assertEquals(1, $ranking->qualified_views);
    }
}
