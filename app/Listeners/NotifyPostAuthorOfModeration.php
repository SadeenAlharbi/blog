<?php

namespace App\Listeners;

use App\Events\PostModeratedByAdmin;
use App\Notifications\AdminActionNotification;
use Illuminate\Support\Str;

/**
 * Notifies an article's author when a moderator edits or removes their article.
 * Silent when the moderator is the author themselves.
 */
class NotifyPostAuthorOfModeration
{
    public function handle(PostModeratedByAdmin $event): void
    {
        $author = $event->post->user;

        // No author on file, or the admin acted on their own article.
        if (! $author || $author->id === $event->actor->id) {
            return;
        }

        $author->notify(new AdminActionNotification($event->action, [
            'title' => Str::limit($event->post->title, 80),
            // A removed article has no page to open; point at the personal area.
            'url' => $event->action === PostModeratedByAdmin::ACTION_DELETED
                ? route('dashboard')
                : route('posts.show', $event->post),
        ]));
    }
}
