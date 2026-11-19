<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Audit;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get()
            ->map(fn (Role $r) => [
                'id'                => $r->id,
                'name'              => $r->name,
                'permissions_count' => $r->permissions_count,
                'users_count'       => $r->users_count,
                'is_super'          => $r->name === User::SUPER_ADMIN,
            ]);

        return Inertia::render('Roles/Index', ['roles' => $roles]);
    }

    public function create(): Response
    {
        return Inertia::render('Roles/Form', [
            'role'   => null,
            'groups' => PermissionCatalog::forFrontend(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        Audit::record('role_created', "Role {$role->name} dibuat", $role, [], ['permissions' => $data['permissions'] ?? []]);

        return to_route('roles.index')->with('success', "Role {$role->name} dibuat.");
    }

    public function edit(Role $role): Response
    {
        abort_if($role->name === User::SUPER_ADMIN, 403);

        return Inertia::render('Roles/Form', [
            'role' => [
                'id'          => $role->id,
                'name'        => $role->name,
                'permissions' => $role->permissions->pluck('name')->values(),
            ],
            'groups' => PermissionCatalog::forFrontend(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->name === User::SUPER_ADMIN, 403);

        $data = $this->validated($request, $role);

        $oldName = $role->name;
        $old = $role->permissions->pluck('name')->sort()->values()->all();

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);
        $role->load('permissions');
        $new = $role->permissions->pluck('name')->sort()->values()->all();

        if ($oldName !== $role->name || $old !== $new) {
            Audit::record('role_updated', "Role {$oldName} diperbarui", $role,
                ['name' => $oldName, 'permissions' => $old],
                ['name' => $role->name, 'permissions' => $new]);
        }

        return to_route('roles.index')->with('success', 'Role diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->name === User::SUPER_ADMIN, 403);

        if ($role->users()->count() > 0) {
            return back()->with('error', 'Role masih dipakai akun. Pindahkan akun tersebut terlebih dahulu.');
        }

        $name = $role->name;
        $role->delete();
        Audit::record('role_deleted', "Role {$name} dihapus", null, ['name' => $name]);

        return back()->with('success', "Role {$name} dihapus.");
    }

    private function validated(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name'          => ['required', 'string', 'max:60', Rule::unique('roles', 'name')->ignore($role?->id),
                                Rule::notIn([User::SUPER_ADMIN])],
            'permissions'   => ['array'],
            'permissions.*' => ['string', Rule::in(PermissionCatalog::names())],
        ], [
            'name.unique' => 'Nama role sudah ada.',
            'name.not_in' => 'Nama role ini dicadangkan sistem.',
        ]);
    }
}
