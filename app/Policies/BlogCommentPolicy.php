<?php

namespace App\Policies;

use App\Models\BlogComment;
use App\Models\User;

class BlogCommentPolicy
{
    /**
     * Moderation (list, inspect, edit, delete) belongs to blogging staff.
     * Commenters have no management endpoints, so they cannot touch anyone's
     * comments, including their own.
     */
    public function viewAny(User $user): bool
    {
        return $user->canManageBlog();
    }

    public function view(User $user, BlogComment $comment): bool
    {
        return $user->canManageBlog();
    }

    public function update(User $user, BlogComment $comment): bool
    {
        return $user->canManageBlog();
    }

    public function delete(User $user, BlogComment $comment): bool
    {
        return $user->canManageBlog();
    }
}
