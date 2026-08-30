<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records which external provider an account is linked to, if any.
 *
 * Two columns rather than a `google_id`: a second provider can be added later
 * by writing a different string, with no further migration. A separate
 * social_accounts table would allow several providers on ONE account, which
 * nothing here asks for — this is the middle ground, not over-engineering.
 *
 * Purely additive and nullable: every existing account keeps signing in with
 * its email and password exactly as before, with both columns null.
 *
 * The unique index is on the PAIR, so two people can never claim the same
 * Google account, while many rows may hold (null, null).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('provider', 32)->nullable()->after('email');
            $table->string('provider_id')->nullable()->after('provider');

            $table->unique(['provider', 'provider_id'], 'users_provider_provider_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_provider_provider_id_unique');
            $table->dropColumn(['provider', 'provider_id']);
        });
    }
};
