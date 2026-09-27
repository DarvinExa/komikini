<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class SystemSettingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any system settings.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('settings.view')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat pengaturan sistem.');
    }

    /**
     * Determine whether the user can view the system setting.
     */
    public function view(User $user, SystemSetting $setting): Response
    {
        return $user->hasPermissionTo('settings.view')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat pengaturan sistem ini.');
    }

    /**
     * Determine whether the user can update the system setting.
     */
    public function update(User $user, SystemSetting $setting): Response
    {
        return $user->hasPermissionTo('settings.manage')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk mengubah pengaturan sistem.');
    }

    /**
     * Determine whether the user can manage system settings.
     */
    public function manage(User $user): Response
    {
        return $user->hasPermissionTo('settings.manage')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk mengelola pengaturan sistem.');
    }
}
