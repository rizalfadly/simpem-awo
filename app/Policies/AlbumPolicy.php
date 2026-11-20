<?php

namespace App\Policies;

use App\Models\Album;
use App\Models\User;

/**
 *  view       : album terbit -> semua anggota; draf/arsip -> pengelola, penerbit, pembuat, atau pengurus/panitia kegiatannya.
 *  contribute : unggah foto/video (gallery.manage, pembuat, atau PIC/panitia kegiatan terkait).
 *  manage     : ubah/hapus album, atur sampul (gallery.manage, pembuat, atau pengelola kegiatan terkait).
 *  publish    : terbit/tarik terbitan, butuh izin content.publish.
 */
class AlbumPolicy
{
    public function view(User $user, Album $album): bool
    {
        return $album->status === 'published'
            || $user->can('content.publish')
            || $this->manage($user, $album)
            || $this->contribute($user, $album);
    }

    public function contribute(User $user, Album $album): bool
    {
        $activity = $album->activity;

        return $user->can('gallery.manage')
            || $album->created_by === $user->id
            || ($activity && ($user->can('manage', $activity) || $activity->hasCommitteeMember($user->member_id)));
    }

    public function manage(User $user, Album $album): bool
    {
        return $user->can('gallery.manage')
            || $album->created_by === $user->id
            || ($album->activity && $user->can('manage', $album->activity));
    }

    public function publish(User $user, Album $album): bool
    {
        return $user->can('content.publish');
    }
}
