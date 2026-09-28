<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Bookmark;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class BookmarkPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the bookmark.
     */
    public function view(User $user, Bookmark $bookmark): Response
    {
        return $user->id === $bookmark->user_id
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat bookmark ini.');
    }

    /**
     * Determine whether the user can delete the bookmark.
     */
    public function delete(User $user, Bookmark $bookmark): Response
    {
        return $user->id === $bookmark->user_id
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk menghapus bookmark ini.');
    }
}
