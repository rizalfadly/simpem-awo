<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

/**
 * Tiga tingkat akses (Super Admin selalu lolos lewat Gate::before):
 *  - view      : ringkasan. Semua anggota untuk kegiatan berstatus publik, atau jika terlibat.
 *  - viewFull  : + proposal, anggaran, LPJ. Izin activities.view, atau pengelola/panitia kegiatan itu.
 *  - manage    : mengubah isi kegiatan. Izin activities.update, atau PIC / pembuat kegiatan.
 */
class ActivityPolicy
{
    public function view(User $user, Activity $activity): bool
    {
        return $activity->isPublic() || $this->viewFull($user, $activity);
    }

    public function viewFull(User $user, Activity $activity): bool
    {
        return $user->can('activities.view')
            || $this->manage($user, $activity)
            || $activity->hasCommitteeMember($user->member_id);
    }

    public function manage(User $user, Activity $activity): bool
    {
        return $user->can('activities.update')
            || ($user->member_id !== null && $user->member_id === $activity->pic_member_id)
            || $activity->created_by === $user->id;
    }
}
