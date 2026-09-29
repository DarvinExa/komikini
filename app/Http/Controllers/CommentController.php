<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\Comment;
use App\Models\CommentReaction;
use App\Models\CommentReport;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    /**
     * List comments for a given comic and chapter.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'comic_slug' => ['required', 'string', 'max:255'],
            'chapter_key' => ['required', 'string', 'max:191'],
        ]);

        $comic = Comic::where('slug', $validated['comic_slug'])->first();
        if (! $comic) {
            return response()->json([
                'success' => true,
                'data' => [],
                'total' => 0,
            ]);
        }

        $currentUser = $request->user();

        $comments = Comment::query()
            ->where('comic_id', $comic->id)
            ->where('chapter_key', $validated['chapter_key'])
            ->root()
            ->with([
                'user:id,name,username,avatar_path',
                'reactions',
                'replies' => function ($query): void {
                    $query->with(['user:id,name,username,avatar_path', 'reactions'])
                        ->orderBy('created_at', 'asc');
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $visibleComments = [];
        $totalCount = 0;

        foreach ($comments as $comment) {
            // Check authorization to view comment
            if (! Gate::forUser($currentUser)->allows('view', $comment)) {
                // If it's deleted and has visible replies, we still allow displaying tombstone
                if ($comment->status === 'deleted' && $comment->replies->isNotEmpty()) {
                    // Allowed as tombstone placeholder for threading
                } else {
                    continue;
                }
            }

            $commentData = $this->transformComment($comment, $currentUser);
            $visibleComments[] = $commentData;
            $totalCount += 1 + count($commentData['replies']);
        }

        return response()->json([
            'success' => true,
            'data' => $visibleComments,
            'total' => $totalCount,
        ]);
    }

    /**
     * Store a newly created comment or single-level reply.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Gate::forUser($user)->authorize('create', Comment::class);

        $validated = $request->validate([
            'comic_slug' => ['required', 'string', 'max:255'],
            'chapter_key' => ['required', 'string', 'max:191'],
            'body' => ['required', 'string', 'min:2', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        $comic = Comic::where('slug', $validated['comic_slug'])->first();
        if (! $comic) {
            $comic = Comic::firstOrCreate(
                ['slug' => $validated['comic_slug']],
                [
                    'title' => ucwords(str_replace('-', ' ', $validated['comic_slug'])),
                    'comic_type' => 'unknown',
                ]
            );
        }

        $parentId = null;
        if (! empty($validated['parent_id'])) {
            /** @var Comment $parent */
            $parent = Comment::findOrFail($validated['parent_id']);

            // INVARIANT: Only single-level replies allowed. Reply-to-reply is strictly forbidden.
            if (! $parent->isRoot()) {
                return response()->json([
                    'message' => 'Balasan bertingkat lebih dari satu tingkat tidak diizinkan.',
                    'errors' => [
                        'parent_id' => ['Balasan bertingkat lebih dari satu tingkat tidak diizinkan.'],
                    ],
                ], 422);
            }

            // Invariant: Parent must be on the same comic and chapter
            if ($parent->comic_id !== $comic->id || $parent->chapter_key !== $validated['chapter_key']) {
                return response()->json([
                    'message' => 'Induk komentar tidak sesuai dengan chapter saat ini.',
                ], 422);
            }

            // Invariant: Parent must be published
            if ($parent->status !== 'published') {
                return response()->json([
                    'message' => 'Tidak dapat membalas komentar yang telah dihapus atau disembunyikan.',
                ], 422);
            }

            $parentId = $parent->id;
        }

        // Anti-XSS: Strip tags and validate length
        $cleanBody = trim(strip_tags($validated['body']));
        if (mb_strlen($cleanBody) < 2) {
            return response()->json([
                'message' => 'Isi komentar minimal 2 karakter teks valid.',
                'errors' => [
                    'body' => ['Isi komentar minimal 2 karakter teks valid.'],
                ],
            ], 422);
        }

        $comment = Comment::create([
            'user_id' => $user->id,
            'comic_id' => $comic->id,
            'chapter_key' => $validated['chapter_key'],
            'parent_id' => $parentId,
            'body' => $cleanBody,
            'status' => 'published',
        ]);

        $comment->load(['user:id,name,username,avatar_path', 'reactions']);

        return response()->json([
            'success' => true,
            'message' => $parentId ? 'Balasan berhasil dikirim.' : 'Komentar berhasil dikirim.',
            'data' => $this->transformComment($comment, $user),
        ], 201);
    }

    /**
     * Update the specified comment.
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Gate::forUser($user)->authorize('update', $comment);

        if ($comment->status !== 'published') {
            return response()->json([
                'message' => 'Komentar yang telah dimoderasi atau dihapus tidak dapat diubah.',
            ], 422);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:1000'],
        ]);

        $cleanBody = trim(strip_tags($validated['body']));
        if (mb_strlen($cleanBody) < 2) {
            return response()->json([
                'message' => 'Isi komentar minimal 2 karakter teks valid.',
                'errors' => [
                    'body' => ['Isi komentar minimal 2 karakter teks valid.'],
                ],
            ], 422);
        }

        $comment->update([
            'body' => $cleanBody,
            'edited_at' => now(),
        ]);

        $comment->load(['user:id,name,username,avatar_path', 'reactions']);

        return response()->json([
            'success' => true,
            'message' => 'Komentar berhasil diperbarui.',
            'data' => $this->transformComment($comment, $user),
        ]);
    }

    /**
     * Remove the specified comment (tombstone if replies exist, hard delete if not).
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Gate::forUser($user)->authorize('delete', $comment);

        $hasReplies = $comment->replies()->exists();

        if ($hasReplies) {
            // Soft tombstone: keep row so reply thread structure is maintained
            $comment->update([
                'status' => 'deleted',
                'body' => '[Komentar ini telah dihapus]',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Komentar telah dihapus (ditandai terhapus karena memiliki balasan).',
                'tombstoned' => true,
            ]);
        }

        // Hard delete: no children to orphan
        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Komentar berhasil dihapus.',
            'tombstoned' => false,
        ]);
    }

    /**
     * Toggle like reaction on a comment.
     */
    public function like(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->isSuspended()) {
            return response()->json([
                'message' => 'Akun yang ditangguhkan tidak dapat memberikan reaksi.',
            ], 403);
        }

        if ($comment->status !== 'published') {
            return response()->json([
                'message' => 'Komentar tidak dapat diberi reaksi.',
            ], 422);
        }

        $existing = CommentReaction::where('comment_id', $comment->id)
            ->where('user_id', $user->id)
            ->where('reaction', 'like')
            ->first();

        if ($existing) {
            $existing->delete();
            $hasLiked = false;
        } else {
            CommentReaction::create([
                'comment_id' => $comment->id,
                'user_id' => $user->id,
                'reaction' => 'like',
            ]);
            $hasLiked = true;
        }

        $likesCount = CommentReaction::where('comment_id', $comment->id)
            ->where('reaction', 'like')
            ->count();

        return response()->json([
            'success' => true,
            'has_liked' => $hasLiked,
            'likes_count' => $likesCount,
        ]);
    }

    /**
     * Report a comment.
     */
    public function report(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Gate::forUser($user)->authorize('create', CommentReport::class);

        // Prevent users from reporting their own comments
        if ($comment->user_id === $user->id) {
            return response()->json([
                'message' => 'Anda tidak dapat melaporkan komentar Anda sendiri.',
            ], 403);
        }

        $validated = $request->validate([
            'reason_code' => ['required', 'string', 'in:spam,harassment,spoiler,inappropriate,other'],
            'details' => ['nullable', 'string', 'max:500'],
        ]);

        $alreadyReported = CommentReport::where('comment_id', $comment->id)
            ->where('reporter_id', $user->id)
            ->where('status', 'open')
            ->exists();

        if ($alreadyReported) {
            return response()->json([
                'message' => 'Anda telah melaporkan komentar ini dan laporan sedang diproses.',
            ], 422);
        }

        CommentReport::create([
            'comment_id' => $comment->id,
            'reporter_id' => $user->id,
            'reason_code' => $validated['reason_code'],
            'details' => ! empty($validated['details']) ? trim(strip_tags($validated['details'])) : null,
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan Anda telah berhasil dikirim dan akan ditinjau oleh tim moderator.',
        ], 201);
    }

    /**
     * Moderate a comment (hide/unhide/delete with reason, logged).
     */
    public function moderate(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Gate::forUser($user)->authorize('moderate', $comment);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:hide,unhide,delete'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $newStatus = match ($validated['action']) {
            'hide' => 'hidden',
            'unhide' => 'published',
            'delete' => 'deleted',
        };

        $reason = trim(strip_tags($validated['reason']));

        $updateData = [
            'status' => $newStatus,
            'moderated_by' => $user->id,
            'moderated_at' => now(),
            'moderation_reason' => $reason,
        ];

        if ($newStatus === 'deleted') {
            $updateData['body'] = '[Komentar ini telah dihapus oleh moderator]';
        }

        $comment->update($updateData);

        return response()->json([
            'success' => true,
            'message' => "Komentar berhasil di-{$validated['action']}.",
            'data' => $this->transformComment($comment, $user),
        ]);
    }

    /**
     * Transform a Comment model into a consistent API array.
     */
    protected function transformComment(Comment $comment, ?User $currentUser): array
    {
        $canModerate = $currentUser ? $currentUser->can('moderate', $comment) : false;

        $replies = [];
        if ($comment->relationLoaded('replies')) {
            foreach ($comment->replies as $reply) {
                if (! Gate::forUser($currentUser)->allows('view', $reply)) {
                    if ($reply->status === 'deleted') {
                        // Tombstone
                    } else {
                        continue;
                    }
                }
                $replies[] = $this->transformComment($reply, $currentUser);
            }
        }

        $body = $comment->status === 'deleted'
            ? '[Komentar ini telah dihapus]'
            : $comment->body;

        return [
            'id' => $comment->id,
            'public_id' => $comment->public_id,
            'parent_id' => $comment->parent_id,
            'user' => [
                'id' => $comment->user?->id,
                'name' => $comment->user?->name ?? 'Pengguna',
                'username' => $comment->user?->username ?? 'user',
                'avatar_url' => $comment->user?->avatar_path ? asset($comment->user->avatar_path) : null,
            ],
            'body' => $body,
            'status' => $comment->status,
            'edited_at' => $comment->edited_at?->toISOString(),
            'created_at' => $comment->created_at?->toISOString(),
            'human_time' => $comment->created_at?->diffForHumans() ?? '',
            'likes_count' => $comment->reactions->where('reaction', 'like')->count(),
            'user_has_liked' => $currentUser
                ? $comment->reactions->where('reaction', 'like')->where('user_id', $currentUser->id)->isNotEmpty()
                : false,
            'can_edit' => $currentUser ? $currentUser->can('update', $comment) : false,
            'can_delete' => $currentUser ? $currentUser->can('delete', $comment) : false,
            'can_moderate' => $canModerate,
            'moderation_reason' => $canModerate ? $comment->moderation_reason : null,
            'replies' => $replies,
        ];
    }
}
