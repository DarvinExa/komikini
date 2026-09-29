<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuditLogController extends Controller
{
    /**
     * Display a listing of audit log entries.
     */
    public function index(Request $request): Response
    {
        $currentUser = $request->user();
        if (! $currentUser || ! $currentUser->hasPermissionTo('audit-logs.view')) {
            abort(403, 'Anda tidak memiliki izin untuk melihat audit logs.');
        }

        $logName = $request->query('log_name');
        $search = $request->query('search');

        $query = Activity::with(['causer:id,name,username', 'subject']);

        if (! empty($logName)) {
            $query->where('log_name', $logName);
        }

        if (! empty($search)) {
            $term = '%'.trim((string) $search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('description', 'like', $term)
                    ->orWhere('properties', 'like', $term);
            });
        }

        $logs = $query->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Activity $act): array => [
                'id' => $act->id,
                'log_name' => $act->log_name,
                'description' => $act->description,
                'properties' => $act->properties,
                'causer' => $act->causer ? [
                    'id' => $act->causer->id,
                    'name' => $act->causer->name,
                    'username' => $act->causer->username,
                ] : null,
                'subject_type' => $act->subject_type ? class_basename($act->subject_type) : null,
                'subject_id' => $act->subject_id,
                'created_at' => $act->created_at?->toISOString(),
                'human_time' => $act->created_at?->diffForHumans() ?? '',
            ]);

        $availableLogNames = Activity::distinct()->pluck('log_name')->filter()->values()->all();

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => [
                'log_name' => $logName ?? '',
                'search' => $search ?? '',
            ],
            'availableLogNames' => $availableLogNames,
        ]);
    }
}
