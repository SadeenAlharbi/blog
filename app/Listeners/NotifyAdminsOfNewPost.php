<?php

namespace App\Listeners;

use App\Events\PostPublished;
use App\Models\User;
use App\Notifications\NewPostNotification;

/**
 * Tells the moderators that a member has published something.
 *
 * Two deliberate exclusions:
 *   - articles written by a moderator (the platform's own posts are not news
 *     to the moderation team);
 *   - the moderator who is also the author, who would otherwise be told about
 *     their own work.
 */
class NotifyAdminsOfNewPost
{
    public function handle(PostPublished $event): void
    {
        $author = $event->post->user;

        if (! $author || $author->isAdmin()) {
            return;
        }

        User::query()
            ->admins()
            ->where('is_active', true)
            ->where('id', '!=', $author->id)
            ->get()
            ->each
            ->notify(new NewPostNotification($event->post));
    }
}
