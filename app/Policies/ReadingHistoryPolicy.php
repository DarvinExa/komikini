<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ReadingHistoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the reading history.
     */
    public function view(User $user, ReadingHistory $history): Response
    {
        return $user->id === $history->user_id
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat riwayat baca ini.');
    }

    /**
     * Determine whether the user can update the reading history.
     */
    public function update(User $user, ReadingHistory $history): Response
    {
        return $user->id === $history->user_id
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk mengubah riwayat baca ini.');
    }

    /**
     * Determine whether the user can delete the reading history.
     */
    public function delete(User $user, ReadingHistory $history): Response
    {
        return $user->id === $history->user_id
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk menghapus riwayat baca ini.');
    }
}
