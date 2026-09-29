<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\RbacService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function __construct(
        protected RbacService $rbacService
    ) {}

    /**
     * Display a listing of users with filters and pagination.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $search = $request->query('search');
        $status = $request->query('status');
        $role = $request->query('role');

        $query = User::query()->with('roles');

        if (! empty($search)) {
            $term = '%'.trim((string) $search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('username', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        if (! empty($status)) {
            $query->where('status', $status);
        }

        if (! empty($role)) {
            $query->whereHas('roles', function ($q) use ($role): void {
                $q->where('name', $role);
            });
        }

        $users = $query->latest('id')
            ->paginate(15)
            ->withQueryString();

        $currentUser = $request->user();

        $transformedUsers = $users->through(function (User $u) use ($currentUser): array {
            return [
                'id' => $u->id,
                'public_id' => $u->public_id,
                'name' => $u->name,
                'username' => $u->username,
                'email' => $u->email,
                'status' => $u->status,
                'suspended_until' => $u->suspended_until?->toISOString(),
                'is_suspended' => $u->isSuspended(),
                'roles' => $u->roles->pluck('name')->all(),
                'created_at' => $u->created_at?->toISOString(),
                'can_suspend' => $currentUser->can('suspend', $u),
                'can_delete' => $currentUser->can('delete', $u),
                'can_assign_roles' => $currentUser->can('assignRoles', $u),
            ];
        });

        $availableRoles = Role::pluck('name')->all();

        return Inertia::render('Admin/Users/Index', [
            'users' => $transformedUsers,
            'filters' => [
                'search' => $search ?? '',
                'status' => $status ?? '',
                'role' => $role ?? '',
            ],
            'availableRoles' => $availableRoles,
        ]);
    }

    /**
     * Suspend a user with password confirmation and reason.
     */
    public function suspend(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('suspend', $user);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'password' => ['required', 'string', 'current_password'],
        ]);

        $until = ! empty($validated['duration_days'])
            ? Carbon::now()->addDays((int) $validated['duration_days'])
            : null;

        try {
            $this->rbacService->suspendUser($request->user(), $user, $until, $validated['reason']);
        } catch (DomainException $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return back()->with('success', "Akun pengguna [{$user->name}] berhasil ditangguhkan.");
    }

    /**
     * Unsuspend a user and restore active status.
     */
    public function unsuspend(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('suspend', $user);

        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        try {
            $this->rbacService->unsuspendUser($request->user(), $user);
        } catch (DomainException $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return back()->with('success', "Status akun pengguna [{$user->name}] berhasil diaktifkan kembali.");
    }

    /**
     * Assign roles to a user with password confirmation and invariant checks.
     */
    public function assignRoles(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('assignRoles', $user);

        $validated = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
            'password' => ['required', 'string', 'current_password'],
        ]);

        try {
            $this->rbacService->assignRoles($request->user(), $user, $validated['roles']);
        } catch (DomainException $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return back()->with('success', "Peran untuk pengguna [{$user->name}] berhasil diperbarui.");
    }

    /**
     * Delete a user with password confirmation and last active superadmin check.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        try {
            $this->rbacService->deleteUser($request->user(), $user);
        } catch (DomainException $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return back()->with('success', "Pengguna [{$user->name}] berhasil dihapus.");
    }
}
