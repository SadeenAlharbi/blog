<?php

namespace App\Events;

use App\Models\Post;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An article moved INTO the published state (a new article published straight
 * away, a draft released, or a schedule that was brought forward).
 *
 * Dispatched once per transition by PostService, never on an edit of an
 * article that was already public.
 */
class PostPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Post $post)
    {
    }
}
