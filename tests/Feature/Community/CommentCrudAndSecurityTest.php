<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use App\Models\Comic;
use App\Models\Comment;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentCrudAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Comic $comic;

    protected string $chapterKey = 'chapter-100';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->user = User::factory()->create([
            'status' => 'active',
        ]);

        $this->comic = Comic::create([
            'slug' => 'one-piece',
            'title' => 'One Piece',
            'comic_type' => 'manga',
        ]);
    }

    public function test_guest_cannot_create_comment(): void
    {
        $response = $this->postJson(route('comments.store'), [
            'comic_slug' => $this->comic->slug,
            'chapter_key' => $this->chapterKey,
            'body' => 'Komentar dari guest',
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_root_comment(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('comments.store'), [
            'comic_slug' => $this->comic->slug,
            'chapter_key' => $this->chapterKey,
            'body' => 'Ini adalah komentar yang sah dan positif.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.body', 'Ini adalah komentar yang sah dan positif.')
            ->assertJsonPath('data.status', 'published');

        $this->assertDatabaseHas('comments', [
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => $this->chapterKey,
            'parent_id' => null,
            'body' => 'Ini adalah komentar yang sah dan positif.',
        ]);
    }

    public function test_xss_payload_in_comment_body_is_stripped_and_stored_safely(): void
    {
        $xssPayload = '<script>alert("XSS")</script><img src="x" onerror="alert(1)">Halo Komikini!<b>Tebal</b>';

        $response = $this->actingAs($this->user)->postJson(route('comments.store'), [
            'comic_slug' => $this->comic->slug,
            'chapter_key' => $this->chapterKey,
            'body' => $xssPayload,
        ]);

        $response->assertCreated();

        // The HTML tags must be stripped
        $comment = Comment::where('user_id', $this->user->id)->first();
        $this->assertNotNull($comment);
        $this->assertStringNotContainsString('<script>', $comment->body);
        $this->assertStringNotContainsString('onerror=', $comment->body);
        $this->assertStringNotContainsString('<b>', $comment->body);
        $this->assertEquals('alert("XSS")Halo Komikini!Tebal', $comment->body);
    }

    public function test_authenticated_user_can_reply_to_root_comment_single_level(): void
    {
        $rootComment = Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => $this->chapterKey,
            'parent_id' => null,
            'body' => 'Komentar induk utama.',
            'status' => 'published',
        ]);

        $replier = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($replier)->postJson(route('comments.store'), [
            'comic_slug' => $this->comic->slug,
            'chapter_key' => $this->chapterKey,
            'parent_id' => $rootComment->id,
            'body' => 'Ini adalah balasan tingkat satu.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.parent_id', $rootComment->id);

        $this->assertDatabaseHas('comments', [
            'user_id' => $replier->id,
            'parent_id' => $rootComment->id,
            'body' => 'Ini adalah balasan tingkat satu.',
        ]);
    }

    public function test_reply_to_reply_is_strictly_rejected_with_422(): void
    {
        $rootComment = Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => $this->chapterKey,
            'parent_id' => null,
            'body' => 'Komentar root tingkat 0.',
            'status' => 'published',
        ]);

        $replyComment = Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => $this->chapterKey,
            'parent_id' => $rootComment->id,
            'body' => 'Balasan tingkat 1.',
            'status' => 'published',
        ]);

        $userC = User::factory()->create(['status' => 'active']);

        // Attempting to reply to a reply ($replyComment has parent_id !== null)
        $response = $this->actingAs($userC)->postJson(route('comments.store'), [
            'comic_slug' => $this->comic->slug,
            'chapter_key' => $this->chapterKey,
            'parent_id' => $replyComment->id,
            'body' => 'Mencoba membuat reply bertingkat dua.',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);

        $this->assertDatabaseMissing('comments', [
            'user_id' => $userC->id,
            'body' => 'Mencoba membuat reply bertingkat dua.',
        ]);
    }

    public function test_user_can_update_own_published_comment(): void
    {
        $comment = Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => $this->chapterKey,
            'parent_id' => null,
            'body' => 'Teks awal sebelum diedit.',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->user)->patchJson(route('comments.update', ['comment' => $comment->id]), [
            'body' => 'Teks yang sudah diperbaiki.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.body', 'Teks yang sudah diperbaiki.');

        $comment->refresh();
        $this->assertEquals('Teks yang sudah diperbaiki.', $comment->body);
        $this->assertNotNull($comment->edited_at);
    }

    public function test_negative_idor_user_cannot_update_another_users_comment(): void
    {
        $attacker = User::factory()->create(['status' => 'active']);

        $comment = Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => $this->chapterKey,
            'parent_id' => null,
            'body' => 'Komentar asli korban.',
            'status' => 'published',
        ]);

        $response = $this->actingAs($attacker)->patchJson(route('comments.update', ['comment' => $comment->id]), [
            'body' => 'Komentar telah diubah oleh hacker!',
        ]);

        $response->assertForbidden();

        $comment->refresh();
        $this->assertEquals('Komentar asli korban.', $comment->body);
    }

    public function test_user_can_delete_own_comment_without_replies_hard_deletes(): void
    {
        $comment = Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => $this->chapterKey,
            'parent_id' => null,
            'body' => 'Komentar tanpa balasan.',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->user)->deleteJson(route('comments.destroy', ['comment' => $comment->id]));

        $response->assertOk()
            ->assertJsonPath('tombstoned', false);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_user_deleting_comment_with_replies_tombstones_comment(): void
    {
        $root = Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => $this->chapterKey,
            'parent_id' => null,
            'body' => 'Komentar utama dengan balasan.',
            'status' => 'published',
        ]);

        $replier = User::factory()->create(['status' => 'active']);
        $reply = Comment::create([
            'user_id' => $replier->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => $this->chapterKey,
            'parent_id' => $root->id,
            'body' => 'Balasan dari replier.',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->user)->deleteJson(route('comments.destroy', ['comment' => $root->id]));

        $response->assertOk()
            ->assertJsonPath('tombstoned', true);

        // Record still exists in database as tombstone
        $this->assertDatabaseHas('comments', [
            'id' => $root->id,
            'status' => 'deleted',
            'body' => '[Komentar ini telah dihapus]',
        ]);

        // Reply still intact
        $this->assertDatabaseHas('comments', [
            'id' => $reply->id,
            'body' => 'Balasan dari replier.',
        ]);
    }

    public function test_negative_idor_user_cannot_delete_another_users_comment(): void
    {
        $attacker = User::factory()->create(['status' => 'active']);

        $comment = Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => $this->chapterKey,
            'parent_id' => null,
            'body' => 'Komentar korban.',
            'status' => 'published',
        ]);

        $response = $this->actingAs($attacker)->deleteJson(route('comments.destroy', ['comment' => $comment->id]));

        $response->assertForbidden();
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    public function test_public_user_can_view_published_comments_list(): void
    {
        Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => $this->chapterKey,
            'parent_id' => null,
            'body' => 'Komentar publik 1.',
            'status' => 'published',
        ]);

        $response = $this->getJson(route('comments.index', [
            'comic_slug' => $this->comic->slug,
            'chapter_key' => $this->chapterKey,
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.body', 'Komentar publik 1.');
    }

    public function test_hidden_comments_are_not_visible_to_guest(): void
    {
        Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => $this->chapterKey,
            'parent_id' => null,
            'body' => 'Komentar yang disembunyikan.',
            'status' => 'hidden',
        ]);

        $response = $this->getJson(route('comments.index', [
            'comic_slug' => $this->comic->slug,
            'chapter_key' => $this->chapterKey,
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }
}
