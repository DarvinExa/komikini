<?php

declare(strict_types=1);

namespace Tests\Feature\Library;

use App\Contracts\ComicProviderInterface;
use App\DTO\Comic\ChapterPayload;
use App\DTO\Comic\ComicDetail;
use App\Enums\ComicType;
use App\Models\Bookmark;
use App\Models\Comic;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_library(): void
    {
        $response = $this->get(route('library.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_library_tabs(): void
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
            'chapter_key' => 'chapter-1109',
            'chapter_number' => '1109',
            'last_image_index' => 4,
            'progress_percent' => 35.0,
            'started_at' => now(),
            'read_at' => now(),
        ]);

        Bookmark::create([
            'user_id' => $user->id,
            'comic_id' => $comic->id,
        ]);

        // 1. Riwayat tab
        $responseRiwayat = $this->actingAs($user)->get(route('library.index', ['tab' => 'riwayat']));
        $responseRiwayat->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Library/Index')
                ->where('tab', 'riwayat')
                ->has('histories.data', 1)
                ->where('histories.data.0.comic_slug', 'one-piece')
                ->where('counts.histories', 1)
                ->where('counts.bookmarks', 1)
            );

        // 2. Bookmark tab
        $responseBookmark = $this->actingAs($user)->get(route('library.index', ['tab' => 'bookmark']));
        $responseBookmark->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Library/Index')
                ->where('tab', 'bookmark')
                ->has('bookmarks.data', 1)
                ->where('bookmarks.data.0.comic_slug', 'one-piece')
            );
    }

    public function test_comic_detail_provides_user_history_and_bookmark_for_continue_reading(): void
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
            'chapter_key' => 'chapter-1109',
            'chapter_number' => '1109',
            'last_image_index' => 4,
            'progress_percent' => 50.0,
            'started_at' => now(),
            'read_at' => now(),
        ]);

        Bookmark::create([
            'user_id' => $user->id,
            'comic_id' => $comic->id,
        ]);

        $mockProvider = Mockery::mock(ComicProviderInterface::class);
        $mockProvider->shouldReceive('detail')->with('one-piece')->andReturn(new ComicDetail(
            slug: 'one-piece',
            title: 'One Piece',
            alternativeTitle: null,
            thumbnailUrl: null,
            comicType: ComicType::MANGA,
            publicationStatus: 'ongoing',
            author: 'Eiichiro Oda',
            synopsis: 'Luffy mencari One Piece.',
            genres: [],
            chapters: [],
            firstChapter: null,
            latestChapter: null,
        ));
        $this->app->instance(ComicProviderInterface::class, $mockProvider);

        $response = $this->actingAs($user)->get(route('comics.detail', ['slug' => 'one-piece']));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Comic/Show')
                ->where('isBookmarked', true)
                ->where('userHistory.chapter_key', 'chapter-1109')
                ->where('userHistory.chapter_number', '1109')
                ->where('userHistory.last_image_index', 4)
            );
    }

    public function test_reader_restores_initial_page_index_from_history(): void
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
            'chapter_key' => 'chapter-1109',
            'chapter_number' => '1109',
            'last_image_index' => 7,
            'progress_percent' => 60.0,
            'started_at' => now(),
            'read_at' => now(),
        ]);

        $mockProvider = Mockery::mock(ComicProviderInterface::class);
        $mockProvider->shouldReceive('chapter')->with('one-piece', 'chapter-1109')->andReturn(new ChapterPayload(
            comicSlug: 'one-piece',
            chapterKey: 'chapter-1109',
            chapterNumber: '1109',
            title: 'One Piece Chapter 1109',
            images: ['https://image2.komiku.to/uploads2/1.jpg'],
            prevChapterKey: null,
            nextChapterKey: null,
        ));
        $this->app->instance(ComicProviderInterface::class, $mockProvider);

        $response = $this->actingAs($user)->get(route('comics.chapter', ['slug' => 'one-piece', 'chapter' => 'chapter-1109']));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Comic/Reader')
                ->where('initialIndex', 7)
            );
    }

    public function test_user_can_delete_individual_and_all_reading_history(): void
    {
        $user = User::factory()->create();
        $comic1 = Comic::create(['slug' => 'c1', 'title' => 'Comic 1', 'comic_type' => 'manga']);
        $comic2 = Comic::create(['slug' => 'c2', 'title' => 'Comic 2', 'comic_type' => 'manga']);

        $h1 = ReadingHistory::create([
            'user_id' => $user->id,
            'comic_id' => $comic1->id,
            'chapter_key' => 'ch-1',
            'chapter_number' => '1',
            'started_at' => now(),
            'read_at' => now(),
        ]);

        $h2 = ReadingHistory::create([
            'user_id' => $user->id,
            'comic_id' => $comic2->id,
            'chapter_key' => 'ch-1',
            'chapter_number' => '1',
            'started_at' => now(),
            'read_at' => now(),
        ]);

        // 1. Delete single history
        $response1 = $this->actingAs($user)->delete(route('library.history.destroy', ['history' => $h1->id]));
        $response1->assertRedirect();
        $this->assertDatabaseMissing('reading_histories', ['id' => $h1->id]);
        $this->assertDatabaseHas('reading_histories', ['id' => $h2->id]);

        // 2. Clear all remaining histories
        $response2 = $this->actingAs($user)->delete(route('library.history.clear'));
        $response2->assertRedirect();
        $this->assertDatabaseCount('reading_histories', 0);
    }

    public function test_user_can_delete_individual_bookmark(): void
    {
        $user = User::factory()->create();
        $comic = Comic::create(['slug' => 'c1', 'title' => 'Comic 1', 'comic_type' => 'manga']);

        $bookmark = Bookmark::create([
            'user_id' => $user->id,
            'comic_id' => $comic->id,
        ]);

        $response = $this->actingAs($user)->delete(route('library.bookmark.destroy', ['bookmark' => $bookmark->id]));
        $response->assertRedirect();
        $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark->id]);
    }
}
