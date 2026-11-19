<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        // Widget hanya dikirim jika pengguna punya hak aksesnya.
        $stats = [];
        if ($user->can('users.view')) {
            $stats[] = ['label' => 'Total akun', 'value' => User::count()];
            $stats[] = ['label' => 'Akun aktif', 'value' => User::where('is_active', true)->count()];
        }
        if ($user->can('roles.view')) {
            $stats[] = ['label' => 'Role', 'value' => Role::count()];
        }

        $recent = $user->can('audit.view')
            ? AuditLog::latest('id')->limit(8)->get()->map(fn (AuditLog $l) => [
                'id'          => $l->id,
                'description' => $l->description,
                'user_label'  => $l->user_label ?? 'Sistem',
                'created_at'  => $l->created_at?->format('d/m/Y H:i'),
            ])
            : null;

        $access = collect(PermissionCatalog::groups())
            ->map(fn ($g) => [
                'label' => $g['label'],
                'items' => collect($g['items'])->filter(fn ($label, $name) => $user->can($name))->values()->all(),
            ])
            ->filter(fn ($g) => count($g['items']) > 0)
            ->values();

        return Inertia::render('Dashboard', [
            'stats'  => $stats,
            'recent' => $recent,
            'access' => $access,
            'last_login_at' => $user->last_login_at?->format('d/m/Y H:i'),
        ]);
    }
}
