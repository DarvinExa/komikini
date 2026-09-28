<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any comments.
     */
    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    /**
     * Determine whether the user can view the comment.
     */
    public function view(?User $user, Comment $comment): Response
    {
        if ($comment->status === 'published') {
            return Response::allow();
        }

        if ($user && ($user->id === $comment->user_id || $user->hasPermissionTo('comments.view'))) {
            return Response::allow();
        }

        return Response::deny('Anda tidak memiliki izin untuk melihat komentar ini.');
    }

    /**
     * Determine whether the user can create comments.
     */
    public function create(User $user): Response
    {
        if ($user->isSuspended()) {
            return Response::deny('Akun yang ditangguhkan tidak dapat membuat komentar.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can update the comment.
     */
    public function update(User $user, Comment $comment): Response
    {
        if ($user->isSuspended()) {
            return Response::deny('Akun yang ditangguhkan tidak dapat mengubah komentar.');
        }

        if ($user->id === $comment->user_id) {
            return Response::allow();
        }

        return Response::deny('Anda hanya dapat mengubah komentar milik Anda sendiri.');
    }

    /**
     * Determine whether the user can delete the comment.
     */
    public function delete(User $user, Comment $comment): Response
    {
        if ($user->isSuspended()) {
            return Response::deny('Akun yang ditangguhkan tidak dapat menghapus komentar.');
        }

        if ($user->id === $comment->user_id || $user->hasPermissionTo('comments.delete')) {
            return Response::allow();
        }

        return Response::deny('Anda tidak memiliki izin untuk menghapus komentar ini.');
    }

    /**
     * Determine whether the user can moderate the comment.
     */
    public function moderate(User $user, Comment $comment): Response
    {
        if ($user->hasPermissionTo('comments.moderate')) {
            return Response::allow();
        }

        return Response::deny('Anda tidak memiliki izin untuk memoderasi komentar.');
    }
}
