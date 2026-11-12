<?php

namespace App\Policies;

use App\Models\User;

/** Editor accounts are created and managed by the admin only. */
class UserPolicy
{
    public function manageEditors(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function updateEditor(User $user, User $editor): bool
    {
        return $this->manageEditors($user) && $editor->isEditor();
    }
}
