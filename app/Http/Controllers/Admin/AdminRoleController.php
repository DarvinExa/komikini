<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionCatalog;
use App\Enums\SystemRole;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\RbacService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class AdminRoleController extends Controller
{
    public function __construct(
        protected RbacService $rbacService
    ) {}

    /**
     * Display roles and permission matrix.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Role::class);

        $currentUser = $request->user();

        $roles = Role::with('permissions')
            ->withCount('users')
            ->get()
            ->map(fn (Role $r): array => [
                'id' => $r->id,
                'name' => $r->name,
                'is_system_role' => SystemRole::isSystemRole($r->name),
                'users_count' => $r->users_count,
                'permissions' => $r->permissions->pluck('name')->all(),
                'can_delete' => $currentUser->can('delete', $r),
                'can_assign_permissions' => $currentUser->can('assignPermissions', $r),
            ]);

        $allPermissions = PermissionCatalog::all();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'allPermissions' => $allPermissions,
            'canCreateRole' => $currentUser->can('create', Role::class),
        ]);
    }

    /**
     * Create a new custom role.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Role::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-z0-9_-]+$/', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'password' => ['required', 'string', 'current_password'],
        ]);

        $role = Role::create([
            'name' => strtolower($validated['name']),
            'guard_name' => 'web',
        ]);

        activity('rbac')
            ->causedBy($request->user())
            ->performedOn($role)
            ->withProperties(['name' => $role->name])
            ->log('role.created');

        if (! empty($validated['permissions'])) {
            try {
                $this->rbacService->assignPermissionsToRole($request->user(), $role, $validated['permissions']);
            } catch (DomainException $e) {
                return back()->withErrors(['message' => $e->getMessage()]);
            }
        }

        return back()->with('success', "Role kustom [{$role->name}] berhasil dibuat.");
    }

    /**
     * Update permissions assigned to a role.
     */
    public function updatePermissions(Request $request, Role $role): RedirectResponse
    {
        Gate::authorize('assignPermissions', $role);

        $validated = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'password' => ['required', 'string', 'current_password'],
        ]);

        try {
            $this->rbacService->assignPermissionsToRole($request->user(), $role, $validated['permissions']);
        } catch (DomainException $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return back()->with('success', "Matriks permission untuk role [{$role->name}] berhasil diperbarui.");
    }

    /**
     * Delete a custom role.
     */
    public function destroy(Request $request, Role $role): RedirectResponse
    {
        Gate::authorize('delete', $role);

        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        try {
            $this->rbacService->deleteRole($request->user(), $role);
        } catch (DomainException $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return back()->with('success', "Role [{$role->name}] berhasil dihapus.");
    }
}
