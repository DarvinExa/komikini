<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_guest_cannot_view_roles(): void
    {
        $response = $this->get('/admin/roles');
        $response->assertRedirect('/login');
    }

    public function test_user_without_roles_view_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::USER->value);

        $response = $this->actingAs($user)->get('/admin/roles');
        $response->assertForbidden();
    }

    public function test_superadmin_can_view_roles_and_permissions_matrix(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        $response = $this->actingAs($superadmin)->get('/admin/roles');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Roles/Index')
            ->has('roles')
            ->has('allPermissions')
            ->where('canCreateRole', true)
        );
    }

    public function test_superadmin_can_create_custom_role_with_password(): void
    {
        $superadmin = User::factory()->create(['password' => bcrypt('SuperSecret123!')]);
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        $response = $this->actingAs($superadmin)->post('/admin/roles', [
            'name' => 'content-creator',
            'permissions' => ['comments.view', 'comments.moderate'],
            'password' => 'SuperSecret123!',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('roles', ['name' => 'content-creator']);
        $role = Role::findByName('content-creator');
        $this->assertTrue($role->hasPermissionTo('comments.view'));
        $this->assertTrue($role->hasPermissionTo('comments.moderate'));
    }

    public function test_cannot_create_role_with_existing_system_role_name(): void
    {
        $superadmin = User::factory()->create(['password' => bcrypt('SuperSecret123!')]);
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        $response = $this->actingAs($superadmin)->post('/admin/roles', [
            'name' => 'admin',
            'permissions' => [],
            'password' => 'SuperSecret123!',
        ]);
        $response->assertSessionHasErrors('name');
    }

    public function test_superadmin_can_update_role_permissions(): void
    {
        $superadmin = User::factory()->create(['password' => bcrypt('SuperSecret123!')]);
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        $role = Role::create(['name' => 'vip-member', 'guard_name' => 'web']);
        $role->givePermissionTo('comments.view');

        $response = $this->actingAs($superadmin)->put("/admin/roles/{$role->id}/permissions", [
            'permissions' => ['comments.view', 'comments.moderate'],
            'password' => 'SuperSecret123!',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue($role->fresh()->hasPermissionTo('comments.moderate'));
    }

    public function test_system_roles_cannot_be_deleted(): void
    {
        $superadmin = User::factory()->create(['password' => bcrypt('SuperSecret123!')]);
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        $adminRole = Role::findByName(SystemRole::ADMIN->value);

        $response = $this->actingAs($superadmin)->delete("/admin/roles/{$adminRole->id}", [
            'password' => 'SuperSecret123!',
        ]);
        // Policy forbids or domain exception prevents deletion
        $this->assertTrue($response->isForbidden() || $response->isRedirect());
        $this->assertDatabaseHas('roles', ['name' => SystemRole::ADMIN->value]);
    }

    public function test_custom_role_can_be_deleted(): void
    {
        $superadmin = User::factory()->create(['password' => bcrypt('SuperSecret123!')]);
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        $customRole = Role::create(['name' => 'temp-reviewer', 'guard_name' => 'web']);

        $response = $this->actingAs($superadmin)->delete("/admin/roles/{$customRole->id}", [
            'password' => 'SuperSecret123!',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('roles', ['name' => 'temp-reviewer']);
    }
}
