<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

/**
 * Blog posts: the admin manages every post; an editor manages only the posts they wrote.
 * Lawyers (and anyone else) get nothing. Inactive accounts get nothing.
 */
class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isEditor());
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function view(User $user, Post $post): bool
    {
        return $this->owns($user, $post);
    }

    public function update(User $user, Post $post): bool
    {
        return $this->owns($user, $post);
    }

    public function publish(User $user, Post $post): bool
    {
        return $this->owns($user, $post);
    }

    public function delete(User $user, Post $post): bool
    {
        return $this->owns($user, $post);
    }

    /** Only the admin sees every author's posts and can filter by author. */
    public function viewAll(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    private function owns(User $user, Post $post): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isAdmin() || ($user->isEditor() && $post->user_id === $user->id);
    }
}
