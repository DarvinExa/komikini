<?php

declare(strict_types=1);

namespace Tests\Feature\Reader;

use App\Jobs\RecordQualifiedView;
use App\Models\Comic;
use App\Models\ComicViewEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RecordQualifiedViewJobTest extends TestCase
{
    use RefreshDatabase;

    protected Comic $comic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->comic = Comic::create([
            'slug' => 'one-piece',
            'title' => 'One Piece',
            'comic_type' => 'manga',
        ]);

        Cache::flush();
    }

    public function test_record_qualified_view_job_creates_qualified_event_on_first_visit(): void
    {
        $job = new RecordQualifiedView(
            comicId: $this->comic->id,
            chapterKey: 'chapter-1',
            userId: null,
            visitorHash: 'hash-abc-123',
            occurredAt: now()
        );

        $job->handle();

        $this->assertDatabaseHas('comic_view_events', [
            'comic_id' => $this->comic->id,
            'chapter_key' => 'chapter-1',
            'visitor_hash' => 'hash-abc-123',
            'qualified' => true,
        ]);

        $this->assertSame(1, ComicViewEvent::count());
    }

    public function test_record_qualified_view_job_marks_duplicate_as_unqualified_within_window(): void
    {
        $now = Carbon::parse('2026-09-28 12:00:00');

        // First view -> qualified
        $job1 = new RecordQualifiedView(
            comicId: $this->comic->id,
            chapterKey: 'chapter-1',
            userId: null,
            visitorHash: 'hash-visitor-xyz',
            occurredAt: $now
        );
        $job1->handle();

        // Second view within 6 hours window (1 hour later) -> unqualified
        $job2 = new RecordQualifiedView(
            comicId: $this->comic->id,
            chapterKey: 'chapter-1',
            userId: null,
            visitorHash: 'hash-visitor-xyz',
            occurredAt: $now->copy()->addHour()
        );
        $job2->handle();

        $events = ComicViewEvent::where('visitor_hash', 'hash-visitor-xyz')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $events);
        $this->assertTrue($events[0]->qualified);
        $this->assertFalse($events[1]->qualified);
    }

    public function test_record_qualified_view_job_allows_new_qualified_view_after_window_expires(): void
    {
        $dedupHours = (int) config('comic.views.deduplication_hours', 6);
        $initialTime = Carbon::parse('2026-09-28 00:00:00');

        // First view -> qualified
        $job1 = new RecordQualifiedView(
            comicId: $this->comic->id,
            chapterKey: 'chapter-1',
            userId: null,
            visitorHash: 'hash-visitor-expired',
            occurredAt: $initialTime
        );
        $job1->handle();

        // Clear cache to test database fallback boundary after window expires
        Cache::flush();

        // View after 7 hours (> 6 hours window) -> qualified again
        $job2 = new RecordQualifiedView(
            comicId: $this->comic->id,
            chapterKey: 'chapter-1',
            userId: null,
            visitorHash: 'hash-visitor-expired',
            occurredAt: $initialTime->copy()->addHours($dedupHours + 1)
        );
        $job2->handle();

        $qualifiedCount = ComicViewEvent::where('visitor_hash', 'hash-visitor-expired')
            ->where('qualified', true)
            ->count();

        $this->assertSame(2, $qualifiedCount);
    }

    public function test_record_qualified_view_job_handles_authenticated_user(): void
    {
        $user = User::factory()->create();

        $job = new RecordQualifiedView(
            comicId: $this->comic->id,
            chapterKey: 'chapter-2',
            userId: $user->id,
            visitorHash: null,
            occurredAt: now()
        );

        $job->handle();

        $this->assertDatabaseHas('comic_view_events', [
            'comic_id' => $this->comic->id,
            'chapter_key' => 'chapter-2',
            'user_id' => $user->id,
            'qualified' => true,
        ]);
    }
}
