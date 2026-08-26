<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the publishing workflow: draft | published | scheduled.
 *
 * `published_at` is KEPT and keeps its meaning (the publish moment). The new
 * column only records intent. The default is "published" so every existing row
 * stays exactly as visible as it is today; the backfill then re-labels rows
 * whose publish date is still in the future as scheduled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('status', 20)->default('published')->after('content');

            $table->index('status');
            $table->index(['status', 'published_at']);
        });

        // Future-dated posts become scheduled; everything else stays published.
        DB::table('posts')
            ->whereNotNull('published_at')
            ->where('published_at', '>', now())
            ->update(['status' => 'scheduled']);
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['status', 'published_at']);
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
