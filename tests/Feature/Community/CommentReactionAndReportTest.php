<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use App\Models\Comic;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentReactionAndReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Comic $comic;

    protected Comment $comment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->user = User::factory()->create(['status' => 'active']);

        $this->comic = Comic::create([
            'slug' => 'one-piece',
            'title' => 'One Piece',
            'comic_type' => 'manga',
        ]);

        $this->comment = Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => 'chapter-100',
            'parent_id' => null,
            'body' => 'Komentar untuk uji reaksi dan pelaporan.',
            'status' => 'published',
        ]);
    }

    public function test_guest_cannot_like_or_report_comment(): void
    {
        $likeResponse = $this->postJson(route('comments.like', ['comment' => $this->comment->id]));
        $likeResponse->assertUnauthorized();

        $reportResponse = $this->postJson(route('comments.report', ['comment' => $this->comment->id]), [
            'reason_code' => 'spam',
        ]);
        $reportResponse->assertUnauthorized();
    }

    public function test_user_can_toggle_like_reaction(): void
    {
        $liker = User::factory()->create(['status' => 'active']);

        // 1. First click: Like
        $response1 = $this->actingAs($liker)->postJson(route('comments.like', ['comment' => $this->comment->id]));
        $response1->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('has_liked', true)
            ->assertJsonPath('likes_count', 1);

        $this->assertDatabaseHas('comment_reactions', [
            'comment_id' => $this->comment->id,
            'user_id' => $liker->id,
            'reaction' => 'like',
        ]);

        // 2. Second click: Unlike
        $response2 = $this->actingAs($liker)->postJson(route('comments.like', ['comment' => $this->comment->id]));
        $response2->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('has_liked', false)
            ->assertJsonPath('likes_count', 0);

        $this->assertDatabaseMissing('comment_reactions', [
            'comment_id' => $this->comment->id,
            'user_id' => $liker->id,
            'reaction' => 'like',
        ]);
    }

    public function test_user_can_report_comment_with_valid_reason_and_details_sanitized(): void
    {
        $reporter = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($reporter)->postJson(route('comments.report', ['comment' => $this->comment->id]), [
            'reason_code' => 'spoiler',
            'details' => '<script>alert(1)</script>Komentar ini membocorkan akhir cerita chapter 101.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $report = CommentReport::where('reporter_id', $reporter->id)->first();
        $this->assertNotNull($report);
        $this->assertEquals('spoiler', $report->reason_code);
        $this->assertEquals('open', $report->status);
        $this->assertStringNotContainsString('<script>', $report->details);
        $this->assertEquals('alert(1)Komentar ini membocorkan akhir cerita chapter 101.', $report->details);
    }

    public function test_duplicate_open_report_by_same_user_is_rejected(): void
    {
        $reporter = User::factory()->create(['status' => 'active']);

        CommentReport::create([
            'comment_id' => $this->comment->id,
            'reporter_id' => $reporter->id,
            'reason_code' => 'spam',
            'status' => 'open',
        ]);

        $response = $this->actingAs($reporter)->postJson(route('comments.report', ['comment' => $this->comment->id]), [
            'reason_code' => 'harassment',
            'details' => 'Laporan kedua dari user yang sama.',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Anda telah melaporkan komentar ini dan laporan sedang diproses.');

        $this->assertDatabaseCount('comment_reports', 1);
    }
}
