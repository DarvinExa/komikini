<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_guest_cannot_view_users_list(): void
    {
        $response = $this->get('/admin/users');
        $response->assertRedirect('/login');
    }

    public function test_user_without_users_view_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::USER->value);

        $response = $this->actingAs($user)->get('/admin/users');
        $response->assertForbidden();
    }

    public function test_superadmin_can_view_users_and_filter(): void
    {
        $superadmin = User::factory()->create(['name' => 'Root Admin', 'username' => 'rootadmin']);
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        $targetUser = User::factory()->create(['name' => 'John Doe', 'username' => 'johndoe', 'email' => 'john@example.com']);
        $targetUser->assignRole(SystemRole::USER->value);

        $response = $this->actingAs($superadmin)->get('/admin/users?search=john');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.username', 'johndoe')
            ->has('availableRoles')
        );
    }

    public function test_admin_can_suspend_user_with_password_and_reason(): void
    {
        $admin = User::factory()->create(['password' => bcrypt('AdminPass123!')]);
        $admin->assignRole(SystemRole::ADMIN->value);
        $admin->givePermissionTo('users.suspend');

        $targetUser = User::factory()->create(['status' => 'active']);
        $targetUser->assignRole(SystemRole::USER->value);

        // Wrong password fails
        $failResponse = $this->actingAs($admin)->post("/admin/users/{$targetUser->id}/suspend", [
            'reason' => 'Melanggar aturan komunitas.',
            'duration_days' => 7,
            'password' => 'WrongPass',
        ]);
        $failResponse->assertSessionHasErrors('password');
        $this->assertEquals('active', $targetUser->fresh()->status);

        // Correct password succeeds
        $successResponse = $this->actingAs($admin)->post("/admin/users/{$targetUser->id}/suspend", [
            'reason' => 'Melanggar aturan komunitas.',
            'duration_days' => 7,
            'password' => 'AdminPass123!',
        ]);
        $successResponse->assertRedirect();
        $successResponse->assertSessionHas('success');

        $refreshed = $targetUser->fresh();
        $this->assertEquals('suspended', $refreshed->status);
        $this->assertNotNull($refreshed->suspended_until);
        $this->assertTrue($refreshed->isSuspended());

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'rbac',
            'description' => 'user.suspended',
            'causer_id' => $admin->id,
            'subject_id' => $targetUser->id,
        ]);
    }

    public function test_admin_can_unsuspend_user_with_password(): void
    {
        $admin = User::factory()->create(['password' => bcrypt('AdminPass123!')]);
        $admin->assignRole(SystemRole::ADMIN->value);
        $admin->givePermissionTo('users.suspend');

        $targetUser = User::factory()->create([
            'status' => 'suspended',
            'suspended_until' => now()->addDays(7),
        ]);
        $targetUser->assignRole(SystemRole::USER->value);

        $response = $this->actingAs($admin)->post("/admin/users/{$targetUser->id}/unsuspend", [
            'password' => 'AdminPass123!',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $refreshed = $targetUser->fresh();
        $this->assertEquals('active', $refreshed->status);
        $this->assertNull($refreshed->suspended_until);
        $this->assertFalse($refreshed->isSuspended());

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'rbac',
            'description' => 'user.unsuspended',
            'causer_id' => $admin->id,
            'subject_id' => $targetUser->id,
        ]);
    }

    public function test_user_cannot_suspend_themselves(): void
    {
        $admin = User::factory()->create(['password' => bcrypt('AdminPass123!')]);
        $admin->assignRole(SystemRole::ADMIN->value);
        $admin->givePermissionTo('users.suspend');

        $response = $this->actingAs($admin)->post("/admin/users/{$admin->id}/suspend", [
            'reason' => 'Mencoba menangguhkan diri sendiri.',
            'password' => 'AdminPass123!',
        ]);
        $response->assertForbidden();
    }

    public function test_superadmin_can_assign_roles_with_password(): void
    {
        $superadmin = User::factory()->create(['password' => bcrypt('SuperSecret123!')]);
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        $targetUser = User::factory()->create();
        $targetUser->assignRole(SystemRole::USER->value);

        $response = $this->actingAs($superadmin)->put("/admin/users/{$targetUser->id}/roles", [
            'roles' => [SystemRole::MODERATOR->value],
            'password' => 'SuperSecret123!',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue($targetUser->fresh()->hasRole(SystemRole::MODERATOR->value));
        $this->assertFalse($targetUser->fresh()->hasRole(SystemRole::USER->value));

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'rbac',
            'description' => 'user.roles_updated',
            'causer_id' => $superadmin->id,
            'subject_id' => $targetUser->id,
        ]);
    }

    public function test_superadmin_can_delete_regular_user(): void
    {
        $superadmin = User::factory()->create(['password' => bcrypt('SuperSecret123!')]);
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        $targetUser = User::factory()->create();
        $targetUser->assignRole(SystemRole::USER->value);

        $response = $this->actingAs($superadmin)->delete("/admin/users/{$targetUser->id}", [
            'password' => 'SuperSecret123!',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
    }

    public function test_cannot_delete_last_active_superadmin(): void
    {
        $superadmin = User::factory()->create(['password' => bcrypt('SuperSecret123!')]);
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        $response = $this->actingAs($superadmin)->delete("/admin/users/{$superadmin->id}", [
            'password' => 'SuperSecret123!',
        ]);
        // Cannot delete oneself (policy forbids) or DomainException
        $this->assertTrue($response->isForbidden() || $response->isRedirect());
        $this->assertDatabaseHas('users', ['id' => $superadmin->id]);
    }
}
