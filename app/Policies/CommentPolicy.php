<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

/**
 * A user may delete only their own comment; an administrator may delete any.
 * Moderation (hide / approve) is admin-only. Enforced server-side — never
 * rely on hiding the UI button.
 */
class CommentPolicy
{
    public function delete(User $user, Comment $comment): bool
    {
        return $user->isAdmin() || $user->id === $comment->user_id;
    }

    public function moderate(User $user, Comment $comment): bool
    {
        return $user->isAdmin();
    }
}
