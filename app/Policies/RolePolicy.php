<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any roles.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('roles.view')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat daftar role.');
    }

    /**
     * Determine whether the user can view the role.
     */
    public function view(User $user, Role $role): Response
    {
        return $user->hasPermissionTo('roles.view')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat role ini.');
    }

    /**
     * Determine whether the user can create roles.
     */
    public function create(User $user): Response
    {
        return $user->hasPermissionTo('roles.create')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk membuat role.');
    }

    /**
     * Determine whether the user can update the role.
     */
    public function update(User $user, Role $role): Response
    {
        return $user->hasPermissionTo('roles.update')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk memperbarui role ini.');
    }

    /**
     * Determine whether the user can delete the role.
     */
    public function delete(User $user, Role $role): Response
    {
        if (SystemRole::isSystemRole($role->name)) {
            return Response::deny("Role sistem '{$role->name}' dilindungi dan tidak dapat dihapus.");
        }

        return $user->hasPermissionTo('roles.delete')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk menghapus role ini.');
    }

    /**
     * Determine whether the user can assign permissions to the role.
     */
    public function assignPermissions(User $user, Role $role): Response
    {
        return $user->hasPermissionTo('roles.assign-permissions')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk menetapkan permission pada role.');
    }
}
