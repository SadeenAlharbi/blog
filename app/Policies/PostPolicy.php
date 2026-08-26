<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

/**
 * Ownership rules for articles. An administrator may act on ANY article;
 * everyone else only on their own. Both the site and the API authorize
 * through these methods, so the rule lives in exactly one place.
 */
class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        return $user->isAdmin() || $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->isAdmin() || $user->id === $post->user_id;
    }

    /** Who may change publication state (publish / unpublish / schedule). */
    public function publish(User $user, Post $post): bool
    {
        return $user->isAdmin() || $user->id === $post->user_id;
    }

    /** Drafts and scheduled posts are readable only by their author or an admin. */
    public function view(?User $user, Post $post): bool
    {
        if ($post->isPublished()) {
            return true;
        }

        return $user !== null && ($user->isAdmin() || $user->id === $post->user_id);
    }
}
