<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\SystemRole;
use App\Models\Activity;
use App\Models\Comic;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_regular_user_without_dashboard_view_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::USER->value);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertForbidden();
    }

    public function test_admin_can_access_dashboard_and_receive_inertia_data(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::ADMIN->value);

        $comic = Comic::create([
            'slug' => 'test-comic',
            'title' => 'Test Comic',
            'comic_type' => 'manga',
        ]);
        $comment = Comment::create([
            'user_id' => $admin->id,
            'comic_id' => $comic->id,
            'chapter_key' => 'ch-1',
            'body' => 'Test comment',
            'status' => 'published',
        ]);
        CommentReport::create([
            'comment_id' => $comment->id,
            'reporter_id' => $admin->id,
            'reason_code' => 'spam',
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('stats')
            ->where('stats.total_users', 1) // admin user
            ->where('stats.open_reports', 1)
            ->where('stats.total_comics', 1)
            ->has('recentActivities')
            ->where('canManageCache', false)
        );
    }

    public function test_superadmin_has_cache_manage_and_can_flush_ranking_cache_with_password(): void
    {
        $superadmin = User::factory()->create(['password' => bcrypt('SuperSecret123!')]);
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        Cache::put('v1:rankings:view:daily', 'cached_daily_data', 3600);
        $this->assertTrue(Cache::has('v1:rankings:view:daily'));

        // Wrong password fails validation
        $failResponse = $this->actingAs($superadmin)->post('/admin/cache/flush', [
            'target' => 'ranking',
            'password' => 'WrongPassword',
        ]);
        $failResponse->assertSessionHasErrors('password');
        $this->assertTrue(Cache::has('v1:rankings:view:daily'));

        // Correct password flushes ranking cache and logs activity
        $successResponse = $this->actingAs($superadmin)->post('/admin/cache/flush', [
            'target' => 'ranking',
            'password' => 'SuperSecret123!',
        ]);
        $successResponse->assertRedirect();
        $successResponse->assertSessionHas('success');

        $this->assertFalse(Cache::has('v1:rankings:view:daily'));

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'cache',
            'description' => 'cache.flushed',
            'causer_id' => $superadmin->id,
        ]);
    }
}
