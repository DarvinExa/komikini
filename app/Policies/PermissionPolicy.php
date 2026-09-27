<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any permissions.
     */
    public function viewAny(User $user): Response
    {
        return ($user->hasPermissionTo('roles.view') || $user->hasPermissionTo('roles.assign-permissions'))
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat daftar permission.');
    }

    /**
     * Determine whether the user can view the permission.
     */
    public function view(User $user, Permission $permission): Response
    {
        return ($user->hasPermissionTo('roles.view') || $user->hasPermissionTo('roles.assign-permissions'))
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat permission ini.');
    }
}
