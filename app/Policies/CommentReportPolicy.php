<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CommentReport;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CommentReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any comment reports.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('reports.view')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat daftar laporan komentar.');
    }

    /**
     * Determine whether the user can view the comment report.
     */
    public function view(User $user, CommentReport $report): Response
    {
        if ($user->id === $report->reporter_id || $user->hasPermissionTo('reports.view')) {
            return Response::allow();
        }

        return Response::deny('Anda tidak memiliki izin untuk melihat laporan ini.');
    }

    /**
     * Determine whether the user can create comment reports.
     */
    public function create(User $user): Response
    {
        if ($user->isSuspended()) {
            return Response::deny('Akun yang ditangguhkan tidak dapat melaporkan komentar.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can resolve the comment report.
     */
    public function resolve(User $user, CommentReport $report): Response
    {
        return $user->hasPermissionTo('reports.resolve')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk menyelesaikan laporan ini.');
    }
}
