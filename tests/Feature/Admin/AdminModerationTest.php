<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\SystemRole;
use App\Models\Comic;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_guest_cannot_access_moderation_queue(): void
    {
        $response = $this->get('/admin/moderation');
        $response->assertRedirect('/login');
    }

    public function test_user_without_moderation_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::USER->value);

        $response = $this->actingAs($user)->get('/admin/moderation');
        $response->assertForbidden();
    }

    public function test_moderator_can_view_moderation_console(): void
    {
        $moderator = User::factory()->create();
        $moderator->assignRole(SystemRole::MODERATOR->value);

        $comic = Comic::create([
            'slug' => 'test-comic-1',
            'title' => 'Test Comic 1',
            'comic_type' => 'manga',
        ]);
        $comment = Comment::create([
            'user_id' => $moderator->id,
            'comic_id' => $comic->id,
            'chapter_key' => 'ch-1',
            'body' => 'Komentar kasar',
            'status' => 'published',
        ]);
        CommentReport::create([
            'comment_id' => $comment->id,
            'reporter_id' => $moderator->id,
            'reason_code' => 'toxic',
            'details' => 'Sangat kasar.',
            'status' => 'open',
        ]);

        $response = $this->actingAs($moderator)->get('/admin/moderation');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Moderation/Index')
            ->has('reports.data', 1)
            ->where('canResolveReports', true)
            ->where('canModerateComments', true)
        );
    }

    public function test_moderator_can_resolve_report_and_hide_comment(): void
    {
        $moderator = User::factory()->create();
        $moderator->assignRole(SystemRole::MODERATOR->value);

        $comic = Comic::create([
            'slug' => 'test-comic-2',
            'title' => 'Test Comic 2',
            'comic_type' => 'manga',
        ]);
        $comment = Comment::create([
            'user_id' => $moderator->id,
            'comic_id' => $comic->id,
            'chapter_key' => 'ch-1',
            'body' => 'Spam link',
            'status' => 'published',
        ]);
        $report = CommentReport::create([
            'comment_id' => $comment->id,
            'reporter_id' => $moderator->id,
            'reason_code' => 'spam',
            'status' => 'open',
        ]);

        $response = $this->actingAs($moderator)->post("/admin/reports/{$report->id}/resolve", [
            'status' => 'resolved',
            'action' => 'hide',
            'reason' => 'Melanggar aturan komunitas terkait spam.',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('resolved', $report->fresh()->status);
        $this->assertEquals($moderator->id, $report->fresh()->resolved_by);

        $commentRefreshed = $comment->fresh();
        $this->assertEquals('hidden', $commentRefreshed->status);
        $this->assertEquals($moderator->id, $commentRefreshed->moderated_by);
        $this->assertEquals('Melanggar aturan komunitas terkait spam.', $commentRefreshed->moderation_reason);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'moderation',
            'description' => 'report.resolved',
            'causer_id' => $moderator->id,
        ]);
    }

    public function test_moderator_can_resolve_report_and_delete_comment(): void
    {
        $moderator = User::factory()->create();
        $moderator->assignRole(SystemRole::MODERATOR->value);

        $comic = Comic::create([
            'slug' => 'test-comic-3',
            'title' => 'Test Comic 3',
            'comic_type' => 'manga',
        ]);
        $comment = Comment::create([
            'user_id' => $moderator->id,
            'comic_id' => $comic->id,
            'chapter_key' => 'ch-1',
            'body' => 'Spam content to be removed',
            'status' => 'published',
        ]);
        $report = CommentReport::create([
            'comment_id' => $comment->id,
            'reporter_id' => $moderator->id,
            'reason_code' => 'illegal',
            'status' => 'open',
        ]);

        $response = $this->actingAs($moderator)->post("/admin/reports/{$report->id}/resolve", [
            'status' => 'resolved',
            'action' => 'delete',
            'reason' => 'Konten ilegal dihapus permanen.',
        ]);
        $response->assertRedirect();

        $commentRefreshed = $comment->fresh();
        $this->assertEquals('deleted', $commentRefreshed->status);
        $this->assertStringContainsString('dihapus oleh moderator', $commentRefreshed->body);
    }

    public function test_moderator_can_bulk_moderate_comments(): void
    {
        $moderator = User::factory()->create();
        $moderator->assignRole(SystemRole::MODERATOR->value);

        $comic = Comic::create([
            'slug' => 'test-comic-4',
            'title' => 'Test Comic 4',
            'comic_type' => 'manga',
        ]);
        $c1 = Comment::create([
            'user_id' => $moderator->id,
            'comic_id' => $comic->id,
            'chapter_key' => 'ch-1',
            'body' => 'Bad comment 1',
            'status' => 'published',
        ]);
        $c2 = Comment::create([
            'user_id' => $moderator->id,
            'comic_id' => $comic->id,
            'chapter_key' => 'ch-1',
            'body' => 'Bad comment 2',
            'status' => 'published',
        ]);

        $response = $this->actingAs($moderator)->post('/admin/comments/bulk-moderate', [
            'comment_ids' => [$c1->id, $c2->id],
            'action' => 'hide',
            'reason' => 'Pembersihan komentar massal.',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('hidden', $c1->fresh()->status);
        $this->assertEquals('hidden', $c2->fresh()->status);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'moderation',
            'description' => 'comments.bulk_moderated',
            'causer_id' => $moderator->id,
        ]);
    }
}
