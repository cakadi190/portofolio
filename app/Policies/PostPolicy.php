<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Editors work on their own posts and unowned drafts; admins on everything.
     */
    public function update(User $user, Post $post): bool
    {
        return $post->isManageableBy($user);
    }

    public function delete(User $user, Post $post): bool
    {
        return $post->isManageableBy($user);
    }

    public function restore(User $user, Post $post): bool
    {
        return $post->isManageableBy($user);
    }

    /**
     * Only admins hand a post to another author.
     */
    public function assignAuthor(User $user): bool
    {
        return $user->isAdmin();
    }
}
