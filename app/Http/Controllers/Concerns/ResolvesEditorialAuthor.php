<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;

trait ResolvesEditorialAuthor
{
    /**
     * Content has no author while it is a draft; whoever publishes it first
     * becomes its author. Only admins may pick the author themselves.
     */
    protected function resolveAuthorId(User $actor, bool $isPublished, ?int $currentAuthorId, ?int $requestedAuthorId): ?int
    {
        $requested = $actor->isAdmin() ? $requestedAuthorId : null;

        return $requested ?? $currentAuthorId ?? ($isPublished ? $actor->getKey() : null);
    }
}
