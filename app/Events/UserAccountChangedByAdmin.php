<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An administrator changed another account's role or activation state.
 */
class UserAccountChangedByAdmin
{
    use Dispatchable, SerializesModels;

    public const ACTION_ROLE_CHANGED = 'role_changed';
    public const ACTION_ACTIVATED = 'account_activated';
    public const ACTION_DEACTIVATED = 'account_deactivated';

    public function __construct(
        public readonly User $user,
        public readonly User $actor,
        public readonly string $action,
    ) {
    }
}
