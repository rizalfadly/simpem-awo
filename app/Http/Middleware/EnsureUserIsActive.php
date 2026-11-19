<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Akun dinonaktifkan saat sesi masih berjalan -> keluarkan.
        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'nomor_anggota' => 'Akun Anda telah dinonaktifkan. Hubungi pengurus.',
            ]);
        }

        // Kata sandi sementara (akun baru / hasil reset) wajib diganti dulu.
        if ($user?->must_change_password && ! $request->routeIs('profile.edit', 'password.update', 'logout')) {
            return redirect()->route('profile.edit')
                ->with('error', 'Ganti kata sandi sementara Anda terlebih dahulu.');
        }

        return $next($request);
    }
}
