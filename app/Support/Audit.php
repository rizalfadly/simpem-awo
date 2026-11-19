<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Audit
{
    /** Label event untuk tampilan & filter. */
    public const EVENTS = [
        'login'         => 'Login',
        'logout'        => 'Logout',
        'login_failed'  => 'Login gagal',
        'created'       => 'Dibuat',
        'updated'       => 'Diubah',
        'deleted'       => 'Dihapus',
        'restored'      => 'Dipulihkan',
        'roles_changed' => 'Peran akun diubah',
        'role_created'  => 'Role dibuat',
        'role_updated'  => 'Role diubah',
        'role_deleted'  => 'Role dihapus',
    ];

    public static function record(
        string $event,
        string $description,
        ?Model $subject = null,
        array $old = [],
        array $new = [],
        ?Authenticatable $actor = null,
    ): AuditLog {
        $actor ??= Auth::user();
        $request = request();

        return AuditLog::create([
            'user_id'        => $actor?->getAuthIdentifier(),
            'user_label'     => $actor ? "{$actor->name} ({$actor->nomor_anggota})" : null,
            'event'          => $event,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id'   => $subject?->getKey(),
            'description'    => Str::limit($description, 250, ''),
            'old_values'     => $old ?: null,
            'new_values'     => $new ?: null,
            'ip_address'     => $request?->ip(),
            'user_agent'     => Str::limit((string) $request?->userAgent(), 250, ''),
        ]);
    }
}
