<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a real role system to users (admin | user) plus an account-active flag.
 *
 * Purely additive: existing rows default to the ordinary "user" role and to
 * active, so nothing that works today changes behaviour. A plain string column
 * (not enum) keeps this portable across MySQL and the SQLite test database and
 * avoids doctrine/dbal for future value changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->after('email');
            $table->boolean('is_active')->default(true)->after('role');

            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
