<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Services\RbacService;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    public function __construct(
        protected RbacService $rbacService,
    ) {}

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('users.view')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat daftar user.');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): Response
    {
        if ($user->id === $model->id || $user->hasPermissionTo('users.view')) {
            return Response::allow();
        }

        return Response::deny('Anda tidak memiliki izin untuk melihat user ini.');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): Response
    {
        if ($user->id === $model->id || $user->hasPermissionTo('users.update')) {
            return Response::allow();
        }

        return Response::deny('Anda tidak memiliki izin untuk memperbarui user ini.');
    }

    /**
     * Determine whether the user can suspend the model.
     */
    public function suspend(User $user, User $model): Response
    {
        if ($user->id === $model->id) {
            return Response::deny('User tidak dapat menangguhkan akun sendiri.');
        }

        if ($this->rbacService->isLastActiveSuperadmin($model)) {
            return Response::deny('Satu-satunya superadmin aktif tidak dapat ditangguhkan.');
        }

        return $user->hasPermissionTo('users.suspend')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk menangguhkan user.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): Response
    {
        if ($this->rbacService->isLastActiveSuperadmin($model)) {
            return Response::deny('Satu-satunya superadmin aktif tidak dapat dihapus.');
        }

        if ($user->id === $model->id || $user->hasPermissionTo('users.delete')) {
            return Response::allow();
        }

        return Response::deny('Anda tidak memiliki izin untuk menghapus user ini.');
    }

    /**
     * Determine whether the user can assign roles to the model.
     */
    public function assignRoles(User $user, User $model): Response
    {
        if ($user->id === $model->id) {
            return Response::deny('User tidak dapat mengubah role sendiri.');
        }

        return $user->hasPermissionTo('users.assign-roles')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk menetapkan role user.');
    }
}
