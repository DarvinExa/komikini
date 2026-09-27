<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Enums\PermissionCatalog;
use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SuperadminAbilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_superadmin_obtains_all_abilities_via_gate_before(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(SystemRole::SUPERADMIN->value);

        // Standard permissions
        $this->assertTrue($superadmin->can(PermissionCatalog::DASHBOARD_VIEW->value));
        $this->assertTrue($superadmin->can(PermissionCatalog::SETTINGS_MANAGE->value));
        $this->assertTrue($superadmin->can(PermissionCatalog::USERS_ASSIGN_ROLES->value));

        // Arbitrary abilities
        $this->assertTrue($superadmin->can('any.arbitrary.unregistered.ability'));
        $this->assertTrue(Gate::forUser($superadmin)->allows('system.deep.maintenance'));
    }

    public function test_admin_and_normal_user_fail_forbidden_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::ADMIN->value);

        $user = User::factory()->create();
        $user->assignRole(SystemRole::USER->value);

        // Admin cannot assign roles or manage settings by default
        $this->assertFalse($admin->can(PermissionCatalog::USERS_ASSIGN_ROLES->value));
        $this->assertFalse($admin->can(PermissionCatalog::SETTINGS_MANAGE->value));
        $this->assertFalse($admin->can('unregistered.ability'));

        // Admin can view dashboard and view users
        $this->assertTrue($admin->can(PermissionCatalog::DASHBOARD_VIEW->value));
        $this->assertTrue($admin->can(PermissionCatalog::USERS_VIEW->value));

        // Normal user cannot view dashboard or moderate comments
        $this->assertFalse($user->can(PermissionCatalog::DASHBOARD_VIEW->value));
        $this->assertFalse($user->can(PermissionCatalog::COMMENTS_MODERATE->value));
        $this->assertFalse($user->can(PermissionCatalog::USERS_VIEW->value));
    }
}
