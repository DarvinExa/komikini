<?php

declare(strict_types=1);

namespace Tests\Feature\Library;

use App\Models\Comic;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ReadingProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_record_reading_progress(): void
    {
        $response = $this->postJson(route('library.progress'), [
            'comic_slug' => 'one-piece',
            'chapter_key' => 'chapter-1109',
            'progress_percent' => 50,
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_record_and_upsert_reading_progress(): void
    {
        $user = User::factory()->create();
        $comic = Comic::create([
            'slug' => 'one-piece',
            'title' => 'One Piece',
            'comic_type' => 'manga',
        ]);

        // First progress event
        $response1 = $this->actingAs($user)->postJson(route('library.progress'), [
            'comic_slug' => 'one-piece',
            'chapter_key' => 'chapter-1109',
            'chapter_number' => '1109',
            'last_image_index' => 5,
            'progress_percent' => 45.5,
        ]);

        $response1->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.last_image_index', 5)
            ->assertJsonPath('data.progress_percent', 45.5);

        $this->assertDatabaseCount('reading_histories', 1);
        $this->assertDatabaseHas('reading_histories', [
            'user_id' => $user->id,
            'comic_id' => $comic->id,
            'chapter_key' => 'chapter-1109',
            'last_image_index' => 5,
        ]);

        // Second progress event on the same comic (should upsert, NOT create duplicate row)
        $response2 = $this->actingAs($user)->postJson(route('library.progress'), [
            'comic_slug' => 'one-piece',
            'chapter_key' => 'chapter-1110',
            'chapter_number' => '1110',
            'last_image_index' => 12,
            'progress_percent' => 96.0,
            'completed' => true,
        ]);

        $response2->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.chapter_key', 'chapter-1110')
            ->assertJsonPath('data.last_image_index', 12)
            ->assertJsonPath('data.progress_percent', fn ($val) => (float) $val === 96.0);

        // Crucial invariant: user_id + comic_id must remain unique (1 row per comic)
        $this->assertDatabaseCount('reading_histories', 1);
        $history = ReadingHistory::first();
        $this->assertNotNull($history);
        $this->assertSame('chapter-1110', $history->chapter_key);
        $this->assertNotNull($history->completed_at);
    }

    public function test_progress_endpoint_rate_limiter_throttles_excessive_requests(): void
    {
        RateLimiter::clear('progress');
        $user = User::factory()->create();
        Comic::create([
            'slug' => 'one-piece',
            'title' => 'One Piece',
            'comic_type' => 'manga',
        ]);

        for ($i = 0; $i < 30; $i++) {
            $res = $this->actingAs($user)->postJson(route('library.progress'), [
                'comic_slug' => 'one-piece',
                'chapter_key' => 'chapter-1',
                'progress_percent' => 10,
            ]);
            $res->assertOk();
        }

        // 31st request should be throttled (429)
        $res31 = $this->actingAs($user)->postJson(route('library.progress'), [
            'comic_slug' => 'one-piece',
            'chapter_key' => 'chapter-1',
            'progress_percent' => 10,
        ]);

        $res31->assertStatus(429);
        RateLimiter::clear('progress');
    }
}
