<?php

namespace App\Listeners;

use App\Events\CommentModeratedByAdmin;
use App\Notifications\AdminActionNotification;
use Illuminate\Support\Str;

/**
 * Notifies a comment's author when a moderator removes it or changes its state.
 * Silent when the moderator is the comment's own author.
 */
class NotifyCommentAuthorOfModeration
{
    public function handle(CommentModeratedByAdmin $event): void
    {
        $author = $event->comment->user;

        if (! $author || $author->id === $event->actor->id) {
            return;
        }

        $post = $event->comment->post;

        $author->notify(new AdminActionNotification($event->action, [
            'title' => $post ? Str::limit($post->title, 80) : null,
            'excerpt' => Str::limit($event->comment->content, 120),
            'url' => $post && ! $post->trashed()
                ? route('posts.show', $post)
                : route('dashboard.comments'),
        ]));
    }
}
