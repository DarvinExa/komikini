<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionCatalog;
use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Create permissions idempotently
        foreach (PermissionCatalog::all() as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        // 2. Create system roles idempotently
        $superadminRole = Role::firstOrCreate([
            'name' => SystemRole::SUPERADMIN->value,
            'guard_name' => 'web',
        ]);

        $adminRole = Role::firstOrCreate([
            'name' => SystemRole::ADMIN->value,
            'guard_name' => 'web',
        ]);

        $moderatorRole = Role::firstOrCreate([
            'name' => SystemRole::MODERATOR->value,
            'guard_name' => 'web',
        ]);

        $userRole = Role::firstOrCreate([
            'name' => SystemRole::USER->value,
            'guard_name' => 'web',
        ]);

        // 3. Sync permissions to roles
        $superadminRole->syncPermissions(PermissionCatalog::all());
        $adminRole->syncPermissions(PermissionCatalog::adminDefault());
        $moderatorRole->syncPermissions(PermissionCatalog::moderatorDefault());
        $userRole->syncPermissions(PermissionCatalog::userDefault());

        // 4. Assign initial superadmin if configured via env without hardcoded password
        $superadminEmail = env('SUPERADMIN_EMAIL');
        if (! empty($superadminEmail)) {
            $user = User::where('email', $superadminEmail)->first();
            if ($user !== null && ! $user->hasRole(SystemRole::SUPERADMIN->value)) {
                $user->assignRole(SystemRole::SUPERADMIN->value);
            }
        }
    }
}
