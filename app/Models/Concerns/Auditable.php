<?php

namespace App\Models\Concerns;

use App\Support\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Mencatat created / updated / deleted / restored ke audit log.
 * Atribut sensitif (mis. password) hanya dicatat sebagai "(diubah)", tanpa nilainya.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $m) {
            $new = Arr::except($m->getAttributes(), $m->auditExcluded());
            foreach ($m->auditSensitive() as $key) {
                if (array_key_exists($key, $m->getAttributes())) {
                    $new[$key] = '(diset)';
                }
            }
            Audit::record('created', class_basename($m).' '.$m->auditLabel().' dibuat', $m, [], $new);
        });

        static::updated(function (Model $m) {
            $changed = $m->getChanges();
            $new = Arr::except($changed, $m->auditExcluded());
            foreach ($m->auditSensitive() as $key) {
                if (array_key_exists($key, $changed)) {
                    $new[$key] = '(diubah)';
                }
            }
            if (! $new) {
                return;
            }
            $old = array_intersect_key($m->getOriginal(), $new);
            foreach ($m->auditSensitive() as $key) {
                if (array_key_exists($key, $old)) {
                    $old[$key] = '(tersembunyi)';
                }
            }
            Audit::record('updated', class_basename($m).' '.$m->auditLabel().' diperbarui', $m, $old, $new);
        });

        static::deleted(fn (Model $m) => Audit::record('deleted', class_basename($m).' '.$m->auditLabel().' dihapus', $m));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $m) => Audit::record('restored', class_basename($m).' '.$m->auditLabel().' dipulihkan', $m));
        }
    }

    public function auditExcluded(): array
    {
        return ['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at', 'last_login_at', 'last_login_ip'];
    }

    public function auditSensitive(): array
    {
        return ['password'];
    }

    public function auditLabel(): string
    {
        return (string) ($this->name ?? '#'.$this->getKey());
    }
}
