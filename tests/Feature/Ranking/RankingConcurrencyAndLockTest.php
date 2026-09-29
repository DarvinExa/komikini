<?php

declare(strict_types=1);

namespace Tests\Feature\Ranking;

use App\Jobs\AggregateComicRankingsJob;
use App\Models\Comic;
use App\Models\ComicViewEvent;
use App\Services\Ranking\RankingAggregationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RankingConcurrencyAndLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_artisan_command_skips_when_lock_is_acquired_by_another_process(): void
    {
        // Simulate an existing active lock
        $lock = Cache::lock('command:aggregate_rankings', 120);
        $this->assertTrue($lock->get());

        // Run the command: it should gracefully exit with success message and skip
        $exitCode = Artisan::call('komik:aggregate-rankings');

        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Perintah dibatalkan: proses agregasi ranking lain sedang berjalan', $output);

        $lock->release();
    }

    public function test_job_skips_when_lock_is_acquired_by_another_process(): void
    {
        $comic = Comic::create(['slug' => 'test-comic', 'title' => 'Test Comic']);
        ComicViewEvent::create([
            'comic_id' => $comic->id,
            'chapter_key' => 'ch-1',
            'occurred_at' => now(),
            'qualified' => true,
        ]);

        // Acquire lock
        $lock = Cache::lock('job:aggregate_comic_rankings', 120);
        $this->assertTrue($lock->get());

        // Dispatch job directly
        $job = new AggregateComicRankingsJob;
        $job->handle(app(RankingAggregationService::class));

        // Since lock was held, job should skip and no ranking should be created yet
        $this->assertDatabaseCount('comic_rankings', 0);

        $lock->release();

        // Now run again with lock released: it should succeed
        $job->handle(app(RankingAggregationService::class));
        $this->assertDatabaseHas('comic_rankings', ['comic_id' => $comic->id]);
    }
}
