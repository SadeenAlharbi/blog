<?php

namespace App\Events;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An administrator acted on someone's article (edited it or removed it).
 *
 * Carrying the actor lets the listener stay silent when an admin acts on their
 * own article — nobody should be notified about their own action.
 */
class PostModeratedByAdmin
{
    use Dispatchable, SerializesModels;

    public const ACTION_UPDATED = 'post_updated';
    public const ACTION_DELETED = 'post_deleted';

    public function __construct(
        public readonly Post $post,
        public readonly User $actor,
        public readonly string $action,
    ) {
    }
}
