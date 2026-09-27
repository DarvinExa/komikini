<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ProtectedRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_system_roles_cannot_be_deleted(): void
    {
        foreach (SystemRole::all() as $roleName) {
            $role = Role::findByName($roleName, 'web');

            $this->expectException(DomainException::class);
            $role->delete();
        }
    }

    public function test_gate_and_role_policy_deny_deletion_of_system_roles(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        $adminRole = Role::findByName(SystemRole::ADMIN->value, 'web');
        $superadminRole = Role::findByName(SystemRole::SUPERADMIN->value, 'web');
        $userRole = Role::findByName(SystemRole::USER->value, 'web');

        // Even superadmin cannot delete system roles
        $this->assertFalse(Gate::forUser($superadmin)->allows('delete', $adminRole));
        $this->assertFalse(Gate::forUser($superadmin)->allows('delete', $superadminRole));
        $this->assertFalse(Gate::forUser($superadmin)->allows('delete', $userRole));
    }

    public function test_custom_roles_can_be_deleted_by_authorized_user(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        $customRole = Role::create(['name' => 'custom-editor', 'guard_name' => 'web']);

        $this->assertTrue(Gate::forUser($superadmin)->allows('delete', $customRole));

        $customRole->delete();

        $this->assertDatabaseMissing('roles', ['name' => 'custom-editor']);
    }
}
