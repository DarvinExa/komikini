<?php

declare(strict_types=1);

namespace Tests\Feature\Library;

use App\Models\Comic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_toggle_bookmark_and_is_redirected_to_login(): void
    {
        $response = $this->post(route('comics.bookmark.toggle', ['slug' => 'one-piece']));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_toggle_bookmark_on_and_off(): void
    {
        $user = User::factory()->create();
        $comic = Comic::create([
            'slug' => 'one-piece',
            'title' => 'One Piece',
            'comic_type' => 'manga',
        ]);

        // 1. Toggle ON
        $responseOn = $this->actingAs($user)->postJson(route('comics.bookmark.toggle', ['slug' => 'one-piece']));
        $responseOn->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_bookmarked', true);

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $user->id,
            'comic_id' => $comic->id,
        ]);
        $this->assertDatabaseCount('bookmarks', 1);

        // 2. Toggle OFF
        $responseOff = $this->actingAs($user)->postJson(route('comics.bookmark.toggle', ['slug' => 'one-piece']));
        $responseOff->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_bookmarked', false);

        $this->assertDatabaseMissing('bookmarks', [
            'user_id' => $user->id,
            'comic_id' => $comic->id,
        ]);
        $this->assertDatabaseCount('bookmarks', 0);
    }
}
