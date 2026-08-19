<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the "Laravel" tag (technology framework name — not a real content
     * category) and detach it from any posts. Non-destructive to posts themselves.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tags')) {
            return;
        }

        $ids = DB::table('tags')
            ->whereRaw('LOWER(slug) = ?', ['laravel'])
            ->orWhereRaw('LOWER(name) = ?', ['laravel'])
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        if (Schema::hasTable('post_tag')) {
            DB::table('post_tag')->whereIn('tag_id', $ids)->delete();
        }

        DB::table('tags')->whereIn('id', $ids)->delete();
    }

    public function down(): void
    {
        // Intentionally irreversible.
    }
};
