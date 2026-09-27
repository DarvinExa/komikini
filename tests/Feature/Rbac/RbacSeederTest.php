<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Enums\PermissionCatalog;
use App\Enums\SystemRole;
use App\Models\Role;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RbacSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_rbac_seeder_is_idempotent_and_can_run_twice_without_error(): void
    {
        // First execution
        $this->seed(RbacSeeder::class);

        $permissionCountFirstRun = Permission::count();
        $roleCountFirstRun = Role::count();

        $this->assertSame(count(PermissionCatalog::all()), $permissionCountFirstRun);
        $this->assertSame(count(SystemRole::all()), $roleCountFirstRun);

        // Second execution
        $this->seed(RbacSeeder::class);

        $this->assertSame($permissionCountFirstRun, Permission::count());
        $this->assertSame($roleCountFirstRun, Role::count());
    }

    public function test_all_catalog_permissions_are_created(): void
    {
        $this->seed(RbacSeeder::class);

        foreach (PermissionCatalog::all() as $permission) {
            $this->assertDatabaseHas('permissions', [
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }

    public function test_default_role_matrices_are_assigned_correctly(): void
    {
        $this->seed(RbacSeeder::class);

        $adminRole = Role::findByName(SystemRole::ADMIN->value, 'web');
        foreach (PermissionCatalog::adminDefault() as $permission) {
            $this->assertTrue($adminRole->hasPermissionTo($permission));
        }
        $this->assertFalse($adminRole->hasPermissionTo(PermissionCatalog::SETTINGS_MANAGE->value));

        $moderatorRole = Role::findByName(SystemRole::MODERATOR->value, 'web');
        foreach (PermissionCatalog::moderatorDefault() as $permission) {
            $this->assertTrue($moderatorRole->hasPermissionTo($permission));
        }
        $this->assertFalse($moderatorRole->hasPermissionTo(PermissionCatalog::USERS_ASSIGN_ROLES->value));

        $userRole = Role::findByName(SystemRole::USER->value, 'web');
        $this->assertTrue($userRole->hasPermissionTo(PermissionCatalog::COMMENTS_VIEW->value));
        $this->assertFalse($userRole->hasPermissionTo(PermissionCatalog::COMMENTS_MODERATE->value));
    }
}
