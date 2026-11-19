<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Super Admin melewati semua pengecekan permission.
        Gate::before(fn (?User $user) => $user?->isSuperAdmin() ? true : null);

        Event::listen(Login::class, function (Login $e) {
            $e->user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => request()->ip(),
            ])->saveQuietly();

            Audit::record('login', 'Berhasil masuk ke sistem', actor: $e->user);
        });

        Event::listen(Logout::class, function (Logout $e) {
            if ($e->user) {
                Audit::record('logout', 'Keluar dari sistem', actor: $e->user);
            }
        });

        Event::listen(Failed::class, function (Failed $e) {
            $nomor = (string) ($e->credentials['nomor_anggota'] ?? '-');
            Audit::record('login_failed', "Percobaan login gagal untuk nomor anggota {$nomor}");
        });
    }
}
