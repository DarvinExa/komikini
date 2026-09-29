<?php

declare(strict_types=1);

namespace Tests\Feature\Release;

use App\Contracts\ComicProviderInterface;
use App\DTO\Comic\ChapterItem;
use App\DTO\Comic\ChapterPayload;
use App\DTO\Comic\ComicDetail;
use App\DTO\Comic\ComicItem;
use App\DTO\Comic\ComicPage;
use App\DTO\Comic\Genre;
use App\Enums\ComicType;
use App\Exceptions\ComicProvider\UpstreamTimeoutException;
use App\Models\Comic;
use App\Models\Comment;
use App\Models\User;
use App\Services\Comic\CachedComicProvider;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReleaseCandidateValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    /**
     * P0-1: Guest can search comics, view details, and open reader.
     */
    public function test_p0_guest_search_detail_and_reader_journey(): void
    {
        $mockProvider = Mockery::mock(ComicProviderInterface::class);

        $dummyItem = new ComicItem(
            slug: 'solo-leveling',
            title: 'Solo Leveling',
            thumbnailUrl: 'https://cdn.example.com/cover.jpg',
            comicType: ComicType::MANHWA,
            latestChapter: 'Chapter 179',
            rating: '9.8',
            description: 'Sung Jinwoo hunters story.'
        );

        $mockProvider->shouldReceive('search')
            ->with('solo', 1)
            ->andReturn(new ComicPage(items: [$dummyItem], currentPage: 1, hasNextPage: false, hasPrevPage: false));

        $mockProvider->shouldReceive('detail')
            ->with('solo-leveling')
            ->andReturn(new ComicDetail(
                slug: 'solo-leveling',
                title: 'Solo Leveling',
                thumbnailUrl: 'https://cdn.example.com/cover.jpg',
                comicType: ComicType::MANHWA,
                publicationStatus: 'Completed',
                author: 'Chugong',
                synopsis: 'Sung Jinwoo hunters story.',
                genres: [new Genre(name: 'Action', slug: 'action')],
                chapters: [
                    new ChapterItem(chapterKey: 'chapter-1', title: 'Chapter 1', chapterNumber: '1', releaseDate: '2020-01-01'),
                ]
            ));

        $mockProvider->shouldReceive('chapter')
            ->with('solo-leveling', 'chapter-1')
            ->andReturn(new ChapterPayload(
                comicSlug: 'solo-leveling',
                chapterKey: 'chapter-1',
                chapterNumber: '1',
                title: 'Chapter 1',
                images: ['https://cdn.example.com/p1.jpg', 'https://cdn.example.com/p2.jpg'],
            ));

        $this->app->instance(ComicProviderInterface::class, $mockProvider);

        // 1. Search
        $searchRes = $this->get('/search?q=solo');
        $searchRes->assertStatus(200);
        $searchRes->assertHeader('X-Correlation-ID');

        // 2. Detail
        $detailRes = $this->get('/komik/solo-leveling');
        $detailRes->assertStatus(200);

        // 3. Reader
        $readerRes = $this->get('/komik/solo-leveling/chapter-1');
        $readerRes->assertStatus(200);
    }

    /**
     * P0-2: User can login and save reading progress.
     */
    public function test_p0_user_save_and_retrieve_reading_progress(): void
    {
        $user = User::factory()->create();

        $comic = Comic::create([
            'title' => 'Tower of God',
            'slug' => 'tower-of-god',
            'cover_url' => 'https://cdn.example.com/tog.jpg',
            'type' => 'manhwa',
            'status' => 'Ongoing',
            'synopsis' => 'Twenty-Fifth Bam climbs the tower.',
        ]);

        $this->actingAs($user)
            ->postJson('/library/progress', [
                'comic_slug' => 'tower-of-god',
                'chapter_key' => 'chapter-50',
                'chapter_number' => '50',
                'last_image_index' => 12,
                'progress_percent' => 75,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('reading_histories', [
            'user_id' => $user->id,
            'comic_id' => $comic->id,
            'chapter_key' => 'chapter-50',
            'last_image_index' => 12,
        ]);

        $libraryRes = $this->actingAs($user)->get('/pustaka');
        $libraryRes->assertStatus(200);
    }

    /**
     * P0-3: User can bookmark and unbookmark comic.
     */
    public function test_p0_user_can_toggle_bookmark(): void
    {
        $user = User::factory()->create();

        $comic = Comic::create([
            'title' => 'Omniscient Reader',
            'slug' => 'omniscient-reader',
            'cover_url' => 'https://cdn.example.com/orv.jpg',
            'type' => 'manhwa',
            'status' => 'Ongoing',
            'synopsis' => 'Only I know the end of the world.',
        ]);

        // Toggle on
        $this->actingAs($user)
            ->post('/komik/omniscient-reader/bookmark')
            ->assertRedirect();

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $user->id,
            'comic_id' => $comic->id,
        ]);

        // Toggle off
        $this->actingAs($user)
            ->post('/komik/omniscient-reader/bookmark')
            ->assertRedirect();

        $this->assertDatabaseMissing('bookmarks', [
            'user_id' => $user->id,
            'comic_id' => $comic->id,
        ]);
    }

    /**
     * P0-4: User comment CRUD and strict IDOR prevention.
     */
    public function test_p0_comment_crud_and_idor_protection(): void
    {
        $author = User::factory()->create();
        $attacker = User::factory()->create();

        $comic = Comic::create([
            'title' => 'Chainsaw Man',
            'slug' => 'chainsaw-man',
            'cover_url' => 'https://cdn.example.com/csm.jpg',
            'type' => 'manga',
            'status' => 'Ongoing',
            'synopsis' => 'Denji devil hunter.',
        ]);

        // 1. Author creates comment
        $this->actingAs($author)->postJson('/comments', [
            'comic_slug' => 'chainsaw-man',
            'chapter_key' => 'chapter-1',
            'body' => 'Chapter yang sangat seru!',
        ])->assertStatus(201);

        $comment = Comment::where('user_id', $author->id)->firstOrFail();
        $this->assertEquals('Chapter yang sangat seru!', $comment->body);

        // 2. Author can update comment
        $this->actingAs($author)->patchJson("/comments/{$comment->id}", [
            'body' => 'Chapter yang sangat seru sekali!',
        ])->assertStatus(200);

        $this->assertEquals('Chapter yang sangat seru sekali!', $comment->fresh()->body);

        // 3. Attacker tries to update author's comment (IDOR) -> 403 Forbidden
        $this->actingAs($attacker)->patchJson("/comments/{$comment->id}", [
            'body' => 'Hacked content',
        ])->assertStatus(403);

        // 4. Attacker tries to delete author's comment (IDOR) -> 403 Forbidden
        $this->actingAs($attacker)->deleteJson("/comments/{$comment->id}")
            ->assertStatus(403);

        // 5. Author deletes own comment -> Success (hard delete if no replies)
        $this->actingAs($author)->deleteJson("/comments/{$comment->id}")
            ->assertStatus(200);

        $this->assertNull($comment->fresh());
    }

    /**
     * P0-5: Moderator can hide reported comment.
     */
    public function test_p0_moderator_can_hide_comment(): void
    {
        $moderator = User::factory()->create();
        $moderator->assignRole('moderator');

        $user = User::factory()->create();

        $comic = Comic::create([
            'title' => 'Jujutsu Kaisen',
            'slug' => 'jujutsu-kaisen',
            'cover_url' => 'https://cdn.example.com/jjk.jpg',
            'type' => 'manga',
            'status' => 'Completed',
            'synopsis' => 'Yuji Itadori curses.',
        ]);

        $comment = Comment::create([
            'user_id' => $user->id,
            'comic_id' => $comic->id,
            'chapter_key' => 'chapter-1',
            'body' => 'Komentar spam yang melanggar aturan',
            'status' => 'published',
        ]);

        $this->actingAs($moderator)->postJson("/comments/{$comment->id}/moderate", [
            'action' => 'hide',
            'reason' => 'Pelanggaran pedoman komunitas: spam',
        ])->assertStatus(200);

        $this->assertEquals('hidden', $comment->fresh()->status);
    }

    /**
     * P0-6: Superadmin can manage role permissions matrix with password confirmation.
     */
    public function test_p0_superadmin_can_update_role_permissions(): void
    {
        $superadmin = User::factory()->create([
            'password' => bcrypt('SuperSecretPass123!'),
        ]);
        $superadmin->assignRole('superadmin');

        $role = Role::findByName('moderator');
        $permission = Permission::firstOrCreate(['name' => 'comments.delete']);

        $this->actingAs($superadmin)->put("/admin/roles/{$role->id}/permissions", [
            'permissions' => [$permission->name],
            'password' => 'SuperSecretPass123!',
        ])->assertRedirect();

        $this->assertTrue($role->fresh()->hasPermissionTo('comments.delete'));
    }

    /**
     * P0-7: Upstream failure serves stale cache gracefully without leaking stack trace.
     */
    public function test_p0_upstream_graceful_stale_fallback(): void
    {
        $comicDetail = new ComicDetail(
            slug: 'bleach',
            title: 'Bleach',
            thumbnailUrl: 'https://cdn.example.com/bleach.jpg',
            comicType: ComicType::MANGA,
            publicationStatus: 'Completed',
            author: 'Tite Kubo',
            synopsis: 'Ichigo Kurosaki soul reaper.',
            genres: [new Genre(name: 'Action', slug: 'action')],
            chapters: []
        );

        // Prime stale cache
        $cachePrefix = (string) config('comic.cache.prefix', 'v1:comic');
        Cache::put("{$cachePrefix}:detail:bleach:stale", $comicDetail->toArray(), 3600);

        // Mock base provider to throw UpstreamTimeoutException
        $mockBaseProvider = Mockery::mock(ComicProviderInterface::class);
        $mockBaseProvider->shouldReceive('detail')
            ->with('bleach')
            ->andThrow(new UpstreamTimeoutException('Connection timeout to upstream provider'));

        $cachedProvider = new CachedComicProvider(
            provider: $mockBaseProvider,
            prefix: $cachePrefix
        );

        $this->app->instance(ComicProviderInterface::class, $cachedProvider);

        $response = $this->get('/komik/bleach');
        $response->assertStatus(200);

        // Verify no stack trace in response
        $content = (string) $response->getContent();
        $this->assertStringNotContainsString('UpstreamTimeoutException', $content);
        $this->assertStringNotContainsString('Stack trace', $content);
    }

    /**
     * Critical Security Invariant: Sole active superadmin cannot be deleted or demoted.
     */
    public function test_sole_active_superadmin_cannot_be_deleted(): void
    {
        $soleSuperadmin = User::factory()->create();
        $soleSuperadmin->assignRole('superadmin');

        $this->actingAs($soleSuperadmin)->delete("/admin/users/{$soleSuperadmin->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('users', ['id' => $soleSuperadmin->id]);
    }
}
