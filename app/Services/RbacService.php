<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonInterface;
use DomainException;

class RbacService
{
    /**
     * Check if the user is the only active superadmin remaining.
     */
    public function isLastActiveSuperadmin(User $user): bool
    {
        if (! $user->hasRole(SystemRole::SUPERADMIN->value)) {
            return false;
        }

        $activeCount = User::role(SystemRole::SUPERADMIN->value)
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('suspended_until')
                    ->orWhere('suspended_until', '<=', now());
            })
            ->count();

        return $activeCount <= 1;
    }

    /**
     * Assign roles to a user while enforcing invariants and logging the audit event.
     *
     * @param  list<string>  $roles
     */
    public function assignRoles(User $actor, User $targetUser, array $roles): void
    {
        if ($actor->id === $targetUser->id) {
            throw new DomainException('User tidak dapat mengubah role sendiri.');
        }

        if ($targetUser->hasRole(SystemRole::SUPERADMIN->value) && ! in_array(SystemRole::SUPERADMIN->value, $roles, true)) {
            if ($this->isLastActiveSuperadmin($targetUser)) {
                throw new DomainException('Satu-satunya superadmin aktif tidak dapat diturunkan.');
            }
        }

        $oldRoles = $targetUser->roles->pluck('name')->toArray();

        $targetUser->syncRoles($roles);

        activity('rbac')
            ->causedBy($actor)
            ->performedOn($targetUser)
            ->withProperties([
                'old_roles' => $oldRoles,
                'new_roles' => $roles,
            ])
            ->log('user.roles_updated');
    }

    /**
     * Assign permissions to a role while enforcing invariants and logging the audit event.
     *
     * @param  list<string>  $permissions
     */
    public function assignPermissionsToRole(User $actor, Role $role, array $permissions): void
    {
        if (! $actor->hasRole(SystemRole::SUPERADMIN->value)) {
            $actorPermissions = $actor->getAllPermissions()->pluck('name')->toArray();
            $missingPermissions = array_diff($permissions, $actorPermissions);

            if (! empty($missingPermissions)) {
                throw new DomainException('Admin tidak dapat memberikan permission yang tidak dimilikinya: '.implode(', ', $missingPermissions));
            }
        }

        $oldPermissions = $role->permissions->pluck('name')->toArray();

        $role->syncPermissions($permissions);

        activity('rbac')
            ->causedBy($actor)
            ->performedOn($role)
            ->withProperties([
                'role' => $role->name,
                'old_permissions' => $oldPermissions,
                'new_permissions' => $permissions,
            ])
            ->log('role.permissions_updated');
    }

    /**
     * Delete a role while enforcing system role protection.
     */
    public function deleteRole(User $actor, Role $role): void
    {
        if (SystemRole::isSystemRole($role->name)) {
            throw new DomainException("Role sistem '{$role->name}' dilindungi dan tidak dapat dihapus.");
        }

        $roleName = $role->name;

        $role->delete();

        activity('rbac')
            ->causedBy($actor)
            ->withProperties(['role' => $roleName])
            ->log('role.deleted');
    }

    /**
     * Delete a user while enforcing the last active superadmin invariant.
     */
    public function deleteUser(User $actor, User $targetUser): void
    {
        if ($this->isLastActiveSuperadmin($targetUser)) {
            throw new DomainException('Satu-satunya superadmin aktif tidak dapat dihapus.');
        }

        $userId = $targetUser->id;
        $userEmail = $targetUser->email;

        $targetUser->delete();

        activity('rbac')
            ->causedBy($actor)
            ->withProperties([
                'user_id' => $userId,
                'email' => $userEmail,
            ])
            ->log('user.deleted');
    }

    /**
     * Suspend a user while enforcing invariants.
     */
    public function suspendUser(User $actor, User $targetUser, ?CarbonInterface $until = null, ?string $reason = null): void
    {
        if ($actor->id === $targetUser->id) {
            throw new DomainException('User tidak dapat menangguhkan akun sendiri.');
        }

        if ($this->isLastActiveSuperadmin($targetUser)) {
            throw new DomainException('Satu-satunya superadmin aktif tidak dapat ditangguhkan.');
        }

        $targetUser->update([
            'status' => 'suspended',
            'suspended_until' => $until,
        ]);

        activity('rbac')
            ->causedBy($actor)
            ->performedOn($targetUser)
            ->withProperties([
                'status' => 'suspended',
                'suspended_until' => $until?->toIso8601String(),
                'reason' => $reason,
            ])
            ->log('user.suspended');
    }

    /**
     * Unsuspend a user and restore active status while logging the audit event.
     */
    public function unsuspendUser(User $actor, User $targetUser): void
    {
        $targetUser->update([
            'status' => 'active',
            'suspended_until' => null,
        ]);

        activity('rbac')
            ->causedBy($actor)
            ->performedOn($targetUser)
            ->withProperties([
                'status' => 'active',
            ])
            ->log('user.unsuspended');
    }
}
