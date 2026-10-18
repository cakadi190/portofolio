<?php

namespace App\Traits\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ownership rules shared by the writable blog content (posts and notes).
 *
 * Admins manage everything. An editor (redaktur) manages only what they
 * wrote, plus drafts nobody owns yet: a draft carries no author until its
 * first publication, which makes the publisher the author.
 */
trait HasEditorialOwnership
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeManageableBy(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(fn (Builder $scope) => $scope
            ->where('user_id', $user->getKey())
            ->orWhere(fn (Builder $unowned) => $unowned->whereNull('user_id')->where('is_published', false)));
    }

    public function isManageableBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->canManageBlog()) {
            return false;
        }

        return $this->user_id === $user->getKey()
            || ($this->user_id === null && ! $this->is_published);
    }
}
