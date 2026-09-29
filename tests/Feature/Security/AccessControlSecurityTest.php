<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Enums\SystemRole;
use App\Models\Bookmark;
use App\Models\Comic;
use App\Models\Comment;
use App\Models\ReadingHistory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_guest_is_redirected_to_login_on_protected_endpoints(): void
    {
        $this->get('/profile')->assertRedirect('/login');
        $this->get('/pustaka')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/users')->assertRedirect('/login');
        $this->get('/admin/roles')->assertRedirect('/login');
        $this->get('/admin/moderation')->assertRedirect('/login');
        $this->get('/admin/audit-logs')->assertRedirect('/login');
    }

    public function test_idor_user_cannot_delete_another_users_bookmark(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $comic = Comic::create([
            'slug' => 'test-comic-idor',
            'title' => 'Test Comic IDOR',
            'comic_type' => 'manga',
        ]);

        $bookmarkB = Bookmark::create([
            'user_id' => $userB->id,
            'comic_id' => $comic->id,
        ]);

        $response = $this->actingAs($userA)->delete("/pustaka/bookmark/{$bookmarkB->id}");
        $response->assertForbidden();

        $this->assertDatabaseHas('bookmarks', ['id' => $bookmarkB->id]);
    }

    public function test_idor_user_cannot_delete_another_users_reading_history(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $comic = Comic::create([
            'slug' => 'test-comic-history',
            'title' => 'Test Comic History',
            'comic_type' => 'manga',
        ]);

        $historyB = ReadingHistory::create([
            'user_id' => $userB->id,
            'comic_id' => $comic->id,
            'chapter_key' => 'ch-1',
            'chapter_number' => '1',
            'last_image_index' => 2,
            'progress_percent' => 50,
            'started_at' => now(),
            'read_at' => now(),
        ]);

        $response = $this->actingAs($userA)->delete("/pustaka/riwayat/{$historyB->id}");
        $response->assertForbidden();

        $this->assertDatabaseHas('reading_histories', ['id' => $historyB->id]);
    }

    public function test_idor_user_cannot_update_another_users_comment(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $comic = Comic::create([
            'slug' => 'test-comic-comment',
            'title' => 'Test Comic Comment',
            'comic_type' => 'manga',
        ]);

        $commentB = Comment::create([
            'user_id' => $userB->id,
            'comic_id' => $comic->id,
            'chapter_key' => 'ch-1',
            'body' => 'Original comment by User B',
            'status' => 'published',
        ]);

        $response = $this->actingAs($userA)->patchJson("/comments/{$commentB->id}", [
            'body' => 'Tampered body by User A',
        ]);
        $response->assertForbidden();

        $this->assertEquals('Original comment by User B', $commentB->fresh()->body);
    }

    public function test_idor_user_cannot_delete_another_users_comment(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $comic = Comic::create([
            'slug' => 'test-comic-del',
            'title' => 'Test Comic Del',
            'comic_type' => 'manga',
        ]);

        $commentB = Comment::create([
            'user_id' => $userB->id,
            'comic_id' => $comic->id,
            'chapter_key' => 'ch-1',
            'body' => 'Original comment by User B',
            'status' => 'published',
        ]);

        $response = $this->actingAs($userA)->deleteJson("/comments/{$commentB->id}");
        $response->assertForbidden();

        $this->assertDatabaseHas('comments', ['id' => $commentB->id]);
    }

    public function test_user_cannot_report_their_own_comment(): void
    {
        $user = User::factory()->create();
        $comic = Comic::create([
            'slug' => 'test-comic-rep',
            'title' => 'Test Comic Rep',
            'comic_type' => 'manga',
        ]);

        $comment = Comment::create([
            'user_id' => $user->id,
            'comic_id' => $comic->id,
            'chapter_key' => 'ch-1',
            'body' => 'My own comment',
            'status' => 'published',
        ]);

        $response = $this->actingAs($user)->postJson("/comments/{$comment->id}/report", [
            'reason_code' => 'spam',
            'details' => 'Reporting myself',
        ]);
        $response->assertForbidden();

        $this->assertDatabaseMissing('comment_reports', ['comment_id' => $comment->id]);
    }

    public function test_regular_user_cannot_escalate_privilege_or_access_admin_console(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::USER->value);

        // Cannot view admin console
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/admin/roles')->assertForbidden();
        $this->actingAs($user)->get('/admin/moderation')->assertForbidden();
        $this->actingAs($user)->get('/admin/audit-logs')->assertForbidden();

        // Cannot assign roles
        $target = User::factory()->create();
        $response = $this->actingAs($user)->put("/admin/users/{$target->id}/roles", [
            'roles' => [SystemRole::SUPERADMIN->value],
            'password' => 'secret',
        ]);
        $response->assertForbidden();
    }

    public function test_system_roles_cannot_be_deleted(): void
    {
        $superadmin = User::factory()->create(['password' => bcrypt('SuperAdmin123!')]);
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        foreach ([SystemRole::SUPERADMIN, SystemRole::ADMIN, SystemRole::MODERATOR, SystemRole::USER] as $sysRole) {
            $role = Role::findByName($sysRole->value);
            $response = $this->actingAs($superadmin)->delete("/admin/roles/{$role->id}", [
                'password' => 'SuperAdmin123!',
            ]);
            $this->assertTrue($response->isForbidden() || $response->isRedirect());
            $this->assertDatabaseHas('roles', ['name' => $sysRole->value]);
        }
    }
}
