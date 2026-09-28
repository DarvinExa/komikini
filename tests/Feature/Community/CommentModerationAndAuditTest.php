<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use App\Enums\SystemRole;
use App\Models\Comic;
use App\Models\Comment;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CommentModerationAndAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $regularUser;

    protected User $moderator;

    protected Comic $comic;

    protected Comment $comment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->regularUser = User::factory()->create(['status' => 'active']);
        $this->regularUser->assignRole(SystemRole::USER->value);

        $this->moderator = User::factory()->create(['status' => 'active']);
        $this->moderator->assignRole(SystemRole::MODERATOR->value);

        $this->comic = Comic::create([
            'slug' => 'one-piece',
            'title' => 'One Piece',
            'comic_type' => 'manga',
        ]);

        $this->comment = Comment::create([
            'user_id' => $this->regularUser->id,
            'comic_id' => $this->comic->id,
            'chapter_key' => 'chapter-100',
            'parent_id' => null,
            'body' => 'Komentar yang melanggar norma komunitas.',
            'status' => 'published',
        ]);
    }

    public function test_regular_user_cannot_moderate_comment(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson(route('comments.moderate', ['comment' => $this->comment->id]), [
            'action' => 'hide',
            'reason' => 'Mencoba moderasi tanpa izin.',
        ]);

        $response->assertForbidden();

        $this->comment->refresh();
        $this->assertEquals('published', $this->comment->status);
    }

    public function test_moderator_can_hide_comment_and_action_is_audit_logged(): void
    {
        $response = $this->actingAs($this->moderator)->postJson(route('comments.moderate', ['comment' => $this->comment->id]), [
            'action' => 'hide',
            'reason' => 'Mengandung kata-kata kasar.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'hidden');

        $this->comment->refresh();
        $this->assertEquals('hidden', $this->comment->status);
        $this->assertEquals($this->moderator->id, $this->comment->moderated_by);
        $this->assertEquals('Mengandung kata-kata kasar.', $this->comment->moderation_reason);

        // Verify Spatie Activity Log was created for the update
        $activity = Activity::where('subject_type', Comment::class)
            ->where('subject_id', $this->comment->id)
            ->where('event', 'updated')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('comment', $activity->log_name);
        $this->assertEquals('hidden', $activity->properties['attributes']['status']);
        $this->assertEquals('Mengandung kata-kata kasar.', $activity->properties['attributes']['moderation_reason']);
    }

    public function test_moderator_can_unhide_comment(): void
    {
        $this->comment->update([
            'status' => 'hidden',
            'moderated_by' => $this->moderator->id,
            'moderated_at' => now(),
            'moderation_reason' => 'Disembunyikan sementara.',
        ]);

        $response = $this->actingAs($this->moderator)->postJson(route('comments.moderate', ['comment' => $this->comment->id]), [
            'action' => 'unhide',
            'reason' => 'Klarifikasi telah diterima, dikembalikan ke publik.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->comment->refresh();
        $this->assertEquals('published', $this->comment->status);
    }

    public function test_moderator_can_delete_comment(): void
    {
        $response = $this->actingAs($this->moderator)->postJson(route('comments.moderate', ['comment' => $this->comment->id]), [
            'action' => 'delete',
            'reason' => 'Pelanggaran berat berulang.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'deleted');

        $this->comment->refresh();
        $this->assertEquals('deleted', $this->comment->status);
        $this->assertEquals('[Komentar ini telah dihapus oleh moderator]', $this->comment->body);
    }
}
