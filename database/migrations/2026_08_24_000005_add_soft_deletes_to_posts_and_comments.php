<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft deletes for articles and comments.
 *
 * Why: when an administrator removes a post or a comment, a reader who is on
 * that page (or follows an old link) previously hit a raw 404. Keeping the row
 * lets the application answer "this was removed by the moderators" instead, and
 * keeps relations and already-sent notifications intact.
 *
 * Purely additive: `deleted_at` is null for every existing row, so nothing that
 * works today changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
