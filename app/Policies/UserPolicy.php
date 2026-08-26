<?php

namespace App\Policies;

use App\Models\User;

/**
 * Account administration.
 *
 * Two tiers of administrator share this policy:
 *
 *   - the SUPER admin (platform owner) — may grant and revoke administrator
 *     access, and may act on other administrators;
 *   - a PROMOTED admin — full day-to-day moderation (articles, comments,
 *     categories, ordinary members) but may NOT create further administrators,
 *     change any administrator's role, or disable an administrator.
 *
 * Every rule lives here rather than in a controller, so the web UI and the API
 * are governed by the same code and neither can be bypassed by calling the
 * other. Two invariants hold for everyone, owner included:
 *   - nobody changes their OWN role or activation (no self-escalation/lockout);
 *   - the LAST active administrator can never be demoted or disabled.
 */
class UserPolicy
{
    /** Who may open the user-administration screens at all. */
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Change someone's role.
     *
     * Restricted to the platform owner: this is the single power that separates
     * the two admin tiers.
     */
    public function updateRole(User $user, User $target): bool
    {
        if (! $user->isAdmin() || $user->id === $target->id) {
            return false;
        }

        // Only the owner hands out or withdraws administrator access.
        if (! $user->isSuperAdmin()) {
            return false;
        }

        // The owner's own role is never editable by anyone else.
        if ($target->isSuperAdmin()) {
            return false;
        }

        // Never leave the platform without an active administrator.
        if ($target->isAdmin() && ! $this->anotherActiveAdminExists($target)) {
            return false;
        }

        return true;
    }

    /**
     * Activate / deactivate an account.
     *
     * A promoted admin may act on ordinary members only. The owner may also act
     * on other administrators — but never on themselves, and never on the last
     * active administrator.
     */
    public function toggleActive(User $user, User $target): bool
    {
        if (! $user->isAdmin() || $user->id === $target->id) {
            return false;
        }

        // The platform owner's account cannot be disabled by anyone.
        if ($target->isSuperAdmin()) {
            return false;
        }

        // A promoted admin may not disable a fellow administrator.
        if ($target->isAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        if ($target->isAdmin() && $target->is_active && ! $this->anotherActiveAdminExists($target)) {
            return false;
        }

        return true;
    }

    /** Is there another ACTIVE administrator besides the target? */
    private function anotherActiveAdminExists(User $target): bool
    {
        return User::admins()
            ->where('is_active', true)
            ->where('id', '!=', $target->id)
            ->exists();
    }
}
