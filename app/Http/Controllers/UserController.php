<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * Sejak V1.1 akun TIDAK dibuat di sini, melainkan lewat Anggota > "Aktifkan akun"
 * (Member = identitas, User = akun akses). Halaman ini mengelola peran, status, dan sandi akun.
 */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $request->user();
        $filters = [
            'q'      => trim((string) $request->query('q', '')),
            'role'   => (string) $request->query('role', ''),
            'status' => (string) $request->query('status', ''),
        ];

        $users = User::query()
            ->with('roles:id,name')
            ->when($filters['q'] !== '', fn ($q) => $q->where(function ($q) use ($filters) {
                $s = '%'.$filters['q'].'%';
                $q->where('name', 'like', $s)->orWhere('nomor_anggota', 'like', $s)->orWhere('email', 'like', $s);
            }))
            ->when($filters['role'] !== '', fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $filters['role'])))
            ->when(in_array($filters['status'], ['active', 'inactive'], true),
                fn ($q) => $q->where('is_active', $filters['status'] === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id'            => $u->id,
                'member_id'     => $u->member_id,
                'nomor_anggota' => $u->nomor_anggota,
                'name'          => $u->name,
                'email'         => $u->email,
                'is_active'     => $u->is_active,
                'roles'         => $u->roles->pluck('name')->values(),
                'last_login_at' => $u->last_login_at?->format('d/m/Y H:i'),
                'is_self'       => $u->is($actor),
                'is_locked'     => $u->isSuperAdmin() && ! $actor->isSuperAdmin(),
            ]);

        return Inertia::render('Users/Index', [
            'users'   => $users,
            'filters' => $filters,
            'roles'   => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function edit(Request $request, User $user): Response
    {
        $this->guardTarget($request->user(), $user);

        return Inertia::render('Users/Form', [
            'account' => [
                'id'            => $user->id,
                'member_id'     => $user->member_id,
                'nomor_anggota' => $user->nomor_anggota,
                'name'          => $user->name,
                'email'         => $user->email,
                'roles'         => $user->getRoleNames()->values(),
            ],
            'roles' => $this->assignableRoles($request->user()),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->guardTarget($actor, $user);

        $rules = [
            'roles'   => ['array'],
            'roles.*' => ['string', Rule::in($this->assignableRoles($actor))],
        ];
        // Akun tanpa anggota (mis. akun teknis) masih boleh diubah nama/emailnya di sini.
        if (! $user->member_id) {
            $rules += [
                'name'  => ['required', 'string', 'max:120'],
                'email' => ['nullable', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            ];
        }

        $data = $request->validate($rules, [
            'email.unique' => 'Email sudah dipakai akun lain.',
            'roles.*.in'   => 'Peran yang dipilih tidak diizinkan.',
        ]);

        $roles = $data['roles'] ?? [];

        // Cegah Super Admin mencabut perannya sendiri (risiko terkunci dari sistem).
        if ($user->is($actor) && $user->isSuperAdmin() && ! in_array(User::SUPER_ADMIN, $roles, true)) {
            throw ValidationException::withMessages(['roles' => 'Anda tidak dapat mencabut peran Super Admin dari akun sendiri.']);
        }

        if (! $user->member_id) {
            $user->update(['name' => $data['name'], 'email' => $data['email'] ?? null]);
        }

        $old = $user->getRoleNames()->sort()->values()->all();
        $user->syncRoles($roles);
        $user->load('roles');
        $new = $user->getRoleNames()->sort()->values()->all();

        if ($old !== $new) {
            Audit::record('roles_changed', "Peran {$user->auditLabel()} diubah", $user, ['roles' => $old], ['roles' => $new]);
        }

        return to_route('users.index')->with('success', 'Akun berhasil diperbarui.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->guardTarget($actor, $user);

        if ($user->is($actor)) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->guardTarget($actor, $user);

        if ($user->is($actor)) {
            return back()->with('error', 'Gunakan halaman Profil untuk mengganti kata sandi Anda sendiri.');
        }

        $plain = Str::password(10, symbols: false);
        $user->update(['password' => $plain, 'must_change_password' => true]);

        return back()
            ->with('success', "Kata sandi {$user->nomor_anggota} direset.")
            ->with('generated_password', ['nomor' => $user->nomor_anggota, 'name' => $user->name, 'password' => $plain]);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->guardTarget($actor, $user);

        if ($user->is($actor)) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete(); // soft delete; keanggotaan tetap ada dan akun bisa diaktifkan lagi dari halaman Anggota

        return back()->with('success', 'Akun dihapus.');
    }

    /** Hanya Super Admin yang boleh menyentuh akun Super Admin. */
    private function guardTarget(User $actor, User $target): void
    {
        abort_if($target->isSuperAdmin() && ! $actor->isSuperAdmin(), 403);
    }

    /** @return string[] */
    private function assignableRoles(User $actor): array
    {
        return Role::query()
            ->when(! $actor->isSuperAdmin(), fn ($q) => $q->where('name', '!=', User::SUPER_ADMIN))
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }
}
