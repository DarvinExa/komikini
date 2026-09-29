<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Comic;
use App\Models\ComicViewEvent;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    /**
     * Display the operational administration dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user || ! $user->hasPermissionTo('dashboard.view')) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses Dashboard Admin.');
        }

        // Summary statistics
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'total_comments' => Comment::count(),
            'open_reports' => CommentReport::where('status', 'open')->count(),
            'total_comics' => Comic::count(),
            'total_qualified_views' => ComicViewEvent::where('qualified', true)->count(),
        ];

        // Recent audit events
        $recentActivities = $user->hasPermissionTo('audit-logs.view')
            ? Activity::with('causer:id,name,username')
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (Activity $act): array => [
                    'id' => $act->id,
                    'log_name' => $act->log_name,
                    'description' => $act->description,
                    'causer' => $act->causer ? [
                        'name' => $act->causer->name,
                        'username' => $act->causer->username,
                    ] : null,
                    'created_at' => $act->created_at?->toISOString(),
                    'human_time' => $act->created_at?->diffForHumans() ?? '',
                ])
                ->all()
            : [];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'canManageCache' => $user->hasPermissionTo('cache.manage'),
        ]);
    }

    /**
     * Flush cache sections with password confirmation.
     */
    public function flushCache(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! $user->hasPermissionTo('cache.manage')) {
            abort(403, 'Anda tidak memiliki izin untuk mengelola cache sistem.');
        }

        $validated = $request->validate([
            'target' => ['required', 'string', 'in:all,ranking,popular,genres,details'],
            'password' => ['required', 'string', 'current_password'],
        ]);

        $target = $validated['target'];

        if ($target === 'all') {
            Cache::flush();
        } elseif ($target === 'ranking') {
            Cache::forget('v1:rankings:view:daily');
            Cache::forget('v1:rankings:view:weekly');
            Cache::forget('v1:rankings:view:monthly');
            Cache::forget('v1:rankings:view:all_time');
            Cache::forget('v1:rankings:daily');
            Cache::forget('v1:rankings:weekly');
            Cache::forget('v1:rankings:monthly');
            Cache::forget('v1:rankings:all_time');
        }

        activity('cache')
            ->causedBy($user)
            ->withProperties(['target' => $target])
            ->log('cache.flushed');

        return back()->with('success', "Cache untuk kategori [{$target}] berhasil dibersihkan.");
    }
}
