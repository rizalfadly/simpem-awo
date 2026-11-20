<?php

namespace App\Policies;

use App\Models\ArchiveDocument;
use App\Models\User;

class ArchiveDocumentPolicy
{
    public function view(User $user, ArchiveDocument $doc): bool
    {
        return $doc->access === 'members'
            || $doc->uploaded_by === $user->id
            || $user->can('archive.view')
            || $user->can('archive.manage');
    }

    public function manage(User $user, ArchiveDocument $doc): bool
    {
        return $user->can('archive.manage');
    }
}
