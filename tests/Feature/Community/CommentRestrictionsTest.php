<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use App\Models\Comic;
use App\Models\Comment;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentRestrictionsTest extends TestCase
{
    use RefreshDatabase;

    protected Comic $comic;

    protected User $suspendedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->comic = Comic::create([
            'slug' => 'one-piece',
            'title' => 'One Piece',
            'comic_type' => 'manga',
        ]);

        $this->suspendedUser = User::factory()->create([
            'status' => 'suspended',
            'suspended_until' => now()->addDays(7),
        ]);
    }

    public function test_suspended_user_cannot_create_comment(): void
    {
        $response = $this->actingAs($this->suspendedUser)->postJson(route('comments.store'), [
            'comic_slug' => $this->comic->slug,
            'chapter_key' => 'chapter-100',
            'body' => 'Komentar dari user yang disuspend.',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('comments', [
            'user_id' => $this->suspendedUser->id,
        ]);
    }

    public function test_suspended_user_cannot_update_own_comment(): void
    {
        $comment = Comment::create([
            'user_id' => $this->suspendedUser->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => 'chapter-100',
            'body' => 'Komentar lama sebelum disuspend.',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->suspendedUser)->patchJson(route('comments.update', ['comment' => $comment->id]), [
            'body' => 'Mencoba mengedit komentar saat suspend.',
        ]);

        $response->assertForbidden();
    }

    public function test_suspended_user_cannot_delete_own_comment(): void
    {
        $comment = Comment::create([
            'user_id' => $this->suspendedUser->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => 'chapter-100',
            'body' => 'Komentar lama.',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->suspendedUser)->deleteJson(route('comments.destroy', ['comment' => $comment->id]));

        $response->assertForbidden();
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    public function test_suspended_user_cannot_like_comment(): void
    {
        $comment = Comment::create([
            'user_id' => User::factory()->create()->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => 'chapter-100',
            'body' => 'Komentar orang lain.',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->suspendedUser)->postJson(route('comments.like', ['comment' => $comment->id]));

        $response->assertForbidden();
    }

    public function test_suspended_user_cannot_report_comment(): void
    {
        $comment = Comment::create([
            'user_id' => User::factory()->create()->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => 'chapter-100',
            'body' => 'Komentar orang lain.',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->suspendedUser)->postJson(route('comments.report', ['comment' => $comment->id]), [
            'reason_code' => 'spam',
        ]);

        $response->assertForbidden();
    }

    public function test_comment_creation_rate_limit_enforced_at_5_per_minute(): void
    {
        $activeUser = User::factory()->create(['status' => 'active']);

        // First 5 requests must succeed
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->actingAs($activeUser)->postJson(route('comments.store'), [
                'comic_slug' => $this->comic->slug,
                'chapter_key' => 'chapter-100',
                'body' => "Komentar valid ke-{$i}",
            ]);
            $response->assertCreated();
        }

        // 6th request must be rate-limited (HTTP 429)
        $response6 = $this->actingAs($activeUser)->postJson(route('comments.store'), [
            'comic_slug' => $this->comic->slug,
            'chapter_key' => 'chapter-100',
            'body' => 'Komentar ke-6 yang melampaui limit.',
        ]);

        $response6->assertStatus(429);
    }

    public function test_comment_report_rate_limit_enforced_at_5_per_hour(): void
    {
        $reporter = User::factory()->create(['status' => 'active']);

        // Create 6 different comments to report
        $comments = [];
        for ($i = 1; $i <= 6; $i++) {
            $comments[] = Comment::create([
                'user_id' => User::factory()->create()->id,
                'comic_id' => $this->comic->id,
                'chapter_key' => 'chapter-100',
                'body' => "Komentar target ke-{$i}",
                'status' => 'published',
            ]);
        }

        // First 5 reports succeed
        for ($i = 0; $i < 5; $i++) {
            $res = $this->actingAs($reporter)->postJson(route('comments.report', ['comment' => $comments[$i]->id]), [
                'reason_code' => 'spam',
            ]);
            $res->assertCreated();
        }

        // 6th report exceeds 5/hour rate limit
        $res6 = $this->actingAs($reporter)->postJson(route('comments.report', ['comment' => $comments[5]->id]), [
            'reason_code' => 'spam',
        ]);

        $res6->assertStatus(429);
    }
}
