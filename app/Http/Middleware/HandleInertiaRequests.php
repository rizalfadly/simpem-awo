<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id'                   => $user->id,
                    'member_id'            => $user->member_id,
                    'name'                 => $user->name,
                    'nomor_anggota'        => $user->nomor_anggota,
                    'email'                => $user->email,
                    'must_change_password' => $user->must_change_password,
                    'roles'                => $user->getRoleNames()->values(),
                    'photo_url'            => $user->member?->photo_path
                        ? Storage::url($user->member->photo_path)
                        : null,
                ] : null,
                // '*' = Super Admin (semua akses)
                'permissions' => $user
                    ? ($user->isSuperAdmin() ? ['*'] : $user->getAllPermissions()->pluck('name')->values())
                    : [],
            ],
            'flash' => [
                'success'            => fn () => $request->session()->get('success'),
                'error'              => fn () => $request->session()->get('error'),
                'generated_password' => fn () => $request->session()->get('generated_password'),
            ],
        ];
    }
}