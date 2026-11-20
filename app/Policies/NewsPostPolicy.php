<?php

namespace App\Policies;

use App\Models\NewsPost;
use App\Models\User;

class NewsPostPolicy
{
    public function view(User $user, NewsPost $post): bool
    {
        return $post->status === 'published' || $user->can('content.publish') || $this->manage($user, $post);
    }

    public function manage(User $user, NewsPost $post): bool
    {
        return $user->can('news.manage') || $post->created_by === $user->id;
    }

    public function publish(User $user, NewsPost $post): bool
    {
        return $user->can('content.publish');
    }
}
