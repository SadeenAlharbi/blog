<?php

namespace App\Events;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An administrator acted on someone's comment (removed it, hid it, approved it,
 * or put it back under review).
 */
class CommentModeratedByAdmin
{
    use Dispatchable, SerializesModels;

    public const ACTION_DELETED = 'comment_deleted';
    public const ACTION_HIDDEN = 'comment_hidden';
    public const ACTION_APPROVED = 'comment_approved';

    public function __construct(
        public readonly Comment $comment,
        public readonly User $actor,
        public readonly string $action,
    ) {
    }
}
