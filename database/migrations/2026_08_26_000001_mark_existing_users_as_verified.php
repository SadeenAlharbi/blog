<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Grandfathers the accounts that already existed before email verification was
 * enforced.
 *
 * Those users registered when no verification link was ever sent, so leaving
 * `email_verified_at` null would silently lock them out of publishing and
 * commenting for something they were never asked to do — including the
 * administrator account created by AdminUserSeeder.
 *
 * Only rows that are already null are touched, and only rows that exist at the
 * moment this runs: every account registered AFTER this migration goes through
 * the real verification flow.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Irreversible by design: which accounts were unverified beforehand is
        // not recorded anywhere, and blanking real verifications would lock
        // active users out.
    }
};
