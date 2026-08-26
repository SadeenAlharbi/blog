<?php

namespace App\Listeners;

use App\Events\UserAccountChangedByAdmin;
use App\Notifications\AdminActionNotification;

/**
 * Notifies a member when a moderator changes their role or activation state.
 * Silent if somehow the actor and the target are the same account (the policy
 * already forbids self-changes, this is the second line of defence).
 */
class NotifyUserOfAccountChange
{
    public function handle(UserAccountChangedByAdmin $event): void
    {
        if ($event->user->id === $event->actor->id) {
            return;
        }

        $event->user->notify(new AdminActionNotification($event->action, [
            'url' => route('dashboard'),
        ]));
    }
}
