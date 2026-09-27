<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Enums\PermissionCatalog;
use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use App\Services\RbacService;
use Database\Seeders\RbacSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SuperadminInvariantTest extends TestCase
{
    use RefreshDatabase;

    protected RbacService $rbacService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->rbacService = app(RbacService::class);
    }

    public function test_sole_active_superadmin_cannot_be_deleted(): void
    {
        $soleSuperadmin = User::factory()->create();
        $soleSuperadmin->assignRole(SystemRole::SUPERADMIN->value);

        $this->assertFalse(Gate::forUser($soleSuperadmin)->allows('delete', $soleSuperadmin));

        $this->expectException(DomainException::class);
        $this->rbacService->deleteUser($soleSuperadmin, $soleSuperadmin);
    }

    public function test_superadmin_can_be_deleted_if_another_active_superadmin_exists(): void
    {
        $superadmin1 = User::factory()->create();
        $superadmin1->assignRole(SystemRole::SUPERADMIN->value);

        $superadmin2 = User::factory()->create();
        $superadmin2->assignRole(SystemRole::SUPERADMIN->value);

        $this->assertTrue(Gate::forUser($superadmin1)->allows('delete', $superadmin2));

        $this->rbacService->deleteUser($superadmin1, $superadmin2);

        $this->assertDatabaseMissing('users', ['id' => $superadmin2->id]);
    }

    public function test_sole_active_superadmin_cannot_be_demoted(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        $anotherAdmin = User::factory()->create();
        $anotherAdmin->assignRole(SystemRole::ADMIN->value);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Satu-satunya superadmin aktif tidak dapat diturunkan.');

        $this->rbacService->assignRoles($anotherAdmin, $superadmin, [SystemRole::ADMIN->value]);
    }

    public function test_user_cannot_modify_own_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::ADMIN->value);

        $this->assertFalse(Gate::forUser($admin)->allows('assignRoles', $admin));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('User tidak dapat mengubah role sendiri.');

        $this->rbacService->assignRoles($admin, $admin, [SystemRole::SUPERADMIN->value]);
    }

    public function test_non_superadmin_admin_cannot_grant_permissions_they_do_not_hold(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::ADMIN->value);

        $customRole = Role::create(['name' => 'custom-role', 'guard_name' => 'web']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Admin tidak dapat memberikan permission yang tidak dimilikinya');

        // Admin default does NOT have settings.manage
        $this->rbacService->assignPermissionsToRole($admin, $customRole, [
            PermissionCatalog::DASHBOARD_VIEW->value,
            PermissionCatalog::SETTINGS_MANAGE->value,
        ]);
    }
}
