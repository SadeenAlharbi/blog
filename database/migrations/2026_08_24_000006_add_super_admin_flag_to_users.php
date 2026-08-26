<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Distinguishes the platform's OWNER administrator from administrators who were
 * promoted later.
 *
 * Both hold the `admin` role and share every day-to-day moderation power. The
 * flag governs one thing only: who may hand out (or take away) administrator
 * access. Without it, any promoted moderator could promote further moderators
 * and the owner would lose control of the platform.
 *
 * The backfill makes the EARLIEST existing administrator the owner, which is the
 * account created by AdminUserSeeder on a normal install.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('role');
        });

        $firstAdminId = DB::table('users')
            ->where('role', 'admin')
            ->orderBy('id')
            ->value('id');

        if ($firstAdminId) {
            DB::table('users')->where('id', $firstAdminId)->update(['is_super_admin' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_super_admin');
        });
    }
};
