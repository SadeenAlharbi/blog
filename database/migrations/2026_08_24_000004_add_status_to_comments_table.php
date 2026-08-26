<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comment moderation: approved | pending | hidden.
 *
 * The default is "approved" so the current flow is untouched — a comment still
 * appears immediately after posting, and every existing comment stays visible.
 * Moderation is therefore post-hoc: an admin can hide a comment (or put it back
 * under review) without a pre-approval queue being imposed on the site.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->string('status', 20)->default('approved')->after('content');

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
