<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdminModerationController extends Controller
{
    /**
     * Display the moderation queue and comments list.
     */
    public function index(Request $request): Response
    {
        $currentUser = $request->user();
        $canViewReports = $currentUser && ($currentUser->hasPermissionTo('reports.view') || $currentUser->hasPermissionTo('reports.resolve'));
        $canModerateComments = $currentUser && $currentUser->hasPermissionTo('comments.moderate');
        $canViewComments = $canModerateComments || $canViewReports;

        if (! $canViewReports && ! $canModerateComments) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses konsol moderasi.');
        }

        $activeTab = $request->query('tab', $canViewReports ? 'reports' : 'comments');

        // Reports data
        $reports = null;
        if ($canViewReports) {
            $reportStatus = $request->query('report_status', 'open');
            $reportsQuery = CommentReport::query()
                ->with([
                    'reporter:id,name,username',
                    'comment.user:id,name,username',
                    'comment.comic:id,slug,title',
                    'resolver:id,name,username',
                ]);

            if ($reportStatus !== 'all') {
                $reportsQuery->where('status', $reportStatus);
            }

            $reports = $reportsQuery->latest('id')
                ->paginate(15, ['*'], 'reports_page')
                ->withQueryString()
                ->through(fn (CommentReport $rep): array => [
                    'id' => $rep->id,
                    'reason_code' => $rep->reason_code,
                    'details' => $rep->details,
                    'status' => $rep->status,
                    'created_at' => $rep->created_at?->toISOString(),
                    'human_time' => $rep->created_at?->diffForHumans() ?? '',
                    'reporter' => $rep->reporter ? [
                        'name' => $rep->reporter->name,
                        'username' => $rep->reporter->username,
                    ] : null,
                    'comment' => $rep->comment ? [
                        'id' => $rep->comment->id,
                        'body' => $rep->comment->body,
                        'status' => $rep->comment->status,
                        'chapter_key' => $rep->comment->chapter_key,
                        'author' => $rep->comment->user ? [
                            'name' => $rep->comment->user->name,
                            'username' => $rep->comment->user->username,
                        ] : null,
                        'comic' => $rep->comment->comic ? [
                            'slug' => $rep->comment->comic->slug,
                            'title' => $rep->comment->comic->title,
                        ] : null,
                    ] : null,
                    'resolver' => $rep->resolver ? [
                        'name' => $rep->resolver->name,
                    ] : null,
                    'resolved_at' => $rep->resolved_at?->toISOString(),
                ]);
        }

        // Comments data
        $comments = null;
        if ($canViewComments) {
            $commentStatus = $request->query('comment_status', 'all');
            $commentSearch = $request->query('comment_search');

            $commentsQuery = Comment::query()
                ->with(['user:id,name,username', 'comic:id,slug,title', 'moderator:id,name,username'])
                ->withCount('reports');

            if ($commentStatus !== 'all' && in_array($commentStatus, ['published', 'hidden', 'deleted'], true)) {
                $commentsQuery->where('status', $commentStatus);
            }

            if (! empty($commentSearch)) {
                $commentsQuery->where('body', 'like', '%'.trim((string) $commentSearch).'%');
            }

            $comments = $commentsQuery->latest('id')
                ->paginate(15, ['*'], 'comments_page')
                ->withQueryString()
                ->through(fn (Comment $c): array => [
                    'id' => $c->id,
                    'public_id' => $c->public_id,
                    'body' => $c->body,
                    'status' => $c->status,
                    'chapter_key' => $c->chapter_key,
                    'reports_count' => $c->reports_count,
                    'moderation_reason' => $c->moderation_reason,
                    'created_at' => $c->created_at?->toISOString(),
                    'human_time' => $c->created_at?->diffForHumans() ?? '',
                    'user' => $c->user ? [
                        'id' => $c->user->id,
                        'name' => $c->user->name,
                        'username' => $c->user->username,
                    ] : null,
                    'comic' => $c->comic ? [
                        'slug' => $c->comic->slug,
                        'title' => $c->comic->title,
                    ] : null,
                    'moderator' => $c->moderator ? [
                        'name' => $c->moderator->name,
                    ] : null,
                ]);
        }

        return Inertia::render('Admin/Moderation/Index', [
            'activeTab' => $activeTab,
            'reports' => $reports,
            'comments' => $comments,
            'canResolveReports' => $currentUser->hasPermissionTo('reports.resolve'),
            'canModerateComments' => $currentUser->hasPermissionTo('comments.moderate'),
            'filters' => [
                'report_status' => $request->query('report_status', 'open'),
                'comment_status' => $request->query('comment_status', 'all'),
                'comment_search' => $request->query('comment_search', ''),
            ],
        ]);
    }

    /**
     * Resolve or dismiss a comment report.
     */
    public function resolveReport(Request $request, CommentReport $report): RedirectResponse
    {
        Gate::authorize('resolve', $report);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:resolved,dismissed'],
            'action' => ['nullable', 'string', 'in:none,hide,delete'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $currentUser = $request->user();

        // Perform moderation action on the reported comment if specified
        if (! empty($validated['action']) && $validated['action'] !== 'none' && $report->comment) {
            $newStatus = $validated['action'] === 'hide' ? 'hidden' : 'deleted';
            $reason = ! empty($validated['reason']) ? trim($validated['reason']) : 'Tindakan dari laporan #'.$report->id;

            $updateData = [
                'status' => $newStatus,
                'moderated_by' => $currentUser->id,
                'moderated_at' => now(),
                'moderation_reason' => $reason,
            ];

            if ($newStatus === 'deleted') {
                $updateData['body'] = '[Komentar ini telah dihapus oleh moderator]';
            }

            $report->comment->update($updateData);
        }

        $report->update([
            'status' => $validated['status'],
            'resolved_by' => $currentUser->id,
            'resolved_at' => now(),
        ]);

        activity('moderation')
            ->causedBy($currentUser)
            ->performedOn($report)
            ->withProperties([
                'status' => $validated['status'],
                'action_taken' => $validated['action'] ?? 'none',
            ])
            ->log('report.resolved');

        return back()->with('success', "Laporan #{$report->id} berhasil diubah statusnya menjadi [{$validated['status']}].");
    }

    /**
     * Bulk moderate comments.
     */
    public function bulkModerateComments(Request $request): RedirectResponse
    {
        $currentUser = $request->user();
        if (! $currentUser || ! $currentUser->hasPermissionTo('comments.moderate')) {
            abort(403, 'Anda tidak memiliki izin untuk memoderasi komentar.');
        }

        $validated = $request->validate([
            'comment_ids' => ['required', 'array', 'min:1', 'max:50'],
            'comment_ids.*' => ['integer', 'exists:comments,id'],
            'action' => ['required', 'string', 'in:hide,unhide,delete'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $newStatus = match ($validated['action']) {
            'hide' => 'hidden',
            'unhide' => 'published',
            'delete' => 'deleted',
        };

        $reason = trim($validated['reason']);

        $updateData = [
            'status' => $newStatus,
            'moderated_by' => $currentUser->id,
            'moderated_at' => now(),
            'moderation_reason' => $reason,
        ];

        if ($newStatus === 'deleted') {
            $updateData['body'] = '[Komentar ini telah dihapus oleh moderator]';
        }

        Comment::whereIn('id', $validated['comment_ids'])->update($updateData);

        activity('moderation')
            ->causedBy($currentUser)
            ->withProperties([
                'action' => $validated['action'],
                'count' => count($validated['comment_ids']),
                'reason' => $reason,
            ])
            ->log('comments.bulk_moderated');

        return back()->with('success', count($validated['comment_ids'])." komentar berhasil di-{$validated['action']}.");
    }
}
