<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    /**
     * Editors work on their own notes and unowned drafts; admins on everything.
     */
    public function update(User $user, Note $note): bool
    {
        return $note->isManageableBy($user);
    }

    public function delete(User $user, Note $note): bool
    {
        return $note->isManageableBy($user);
    }

    public function restore(User $user, Note $note): bool
    {
        return $note->isManageableBy($user);
    }

    /**
     * Only admins hand a note to another author.
     */
    public function assignAuthor(User $user): bool
    {
        return $user->isAdmin();
    }
}
