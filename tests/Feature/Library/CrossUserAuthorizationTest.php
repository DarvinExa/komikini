<?php

declare(strict_types=1);

namespace Tests\Feature\Library;

use App\Models\Bookmark;
use App\Models\Comic;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossUserAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_delete_another_users_reading_history(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $comic = Comic::create([
            'slug' => 'one-piece',
            'title' => 'One Piece',
            'comic_type' => 'manga',
        ]);

        $historyB = ReadingHistory::create([
            'user_id' => $userB->id,
            'comic_id' => $comic->id,
            'chapter_key' => 'chapter-1109',
            'chapter_number' => '1109',
            'started_at' => now(),
            'read_at' => now(),
        ]);

        // User A attempts to delete User B's history
        $response = $this->actingAs($userA)->delete(route('library.history.destroy', ['history' => $historyB->id]));

        $response->assertForbidden();
        $this->assertDatabaseHas('reading_histories', ['id' => $historyB->id]);
    }

    public function test_user_cannot_delete_another_users_bookmark(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $comic = Comic::create([
            'slug' => 'one-piece',
            'title' => 'One Piece',
            'comic_type' => 'manga',
        ]);

        $bookmarkB = Bookmark::create([
            'user_id' => $userB->id,
            'comic_id' => $comic->id,
        ]);

        // User A attempts to delete User B's bookmark
        $response = $this->actingAs($userA)->delete(route('library.bookmark.destroy', ['bookmark' => $bookmarkB->id]));

        $response->assertForbidden();
        $this->assertDatabaseHas('bookmarks', ['id' => $bookmarkB->id]);
    }

    public function test_user_library_never_leaks_another_users_history_or_bookmarks(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $comic = Comic::create([
            'slug' => 'one-piece',
            'title' => 'One Piece',
            'comic_type' => 'manga',
        ]);

        ReadingHistory::create([
            'user_id' => $userB->id,
            'comic_id' => $comic->id,
            'chapter_key' => 'chapter-secret',
            'chapter_number' => '999',
            'started_at' => now(),
            'read_at' => now(),
        ]);

        Bookmark::create([
            'user_id' => $userB->id,
            'comic_id' => $comic->id,
        ]);

        // User A accesses library
        $response = $this->actingAs($userA)->get(route('library.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Library/Index')
                ->has('histories.data', 0)
                ->where('counts.histories', 0)
                ->where('counts.bookmarks', 0)
            );
    }

    public function test_deleting_user_cascades_and_removes_their_reading_histories_and_bookmarks(): void
    {
        $user = User::factory()->create();
        $comic = Comic::create([
            'slug' => 'one-piece',
            'title' => 'One Piece',
            'comic_type' => 'manga',
        ]);

        ReadingHistory::create([
            'user_id' => $user->id,
            'comic_id' => $comic->id,
            'chapter_key' => 'chapter-1',
            'chapter_number' => '1',
            'started_at' => now(),
            'read_at' => now(),
        ]);

        Bookmark::create([
            'user_id' => $user->id,
            'comic_id' => $comic->id,
        ]);

        $this->assertDatabaseCount('reading_histories', 1);
        $this->assertDatabaseCount('bookmarks', 1);

        $user->delete();

        $this->assertDatabaseCount('reading_histories', 0);
        $this->assertDatabaseCount('bookmarks', 0);
    }
}
