<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Two changes to `tags`, both additive:
 *
 *  1. `deleted_at` — a removed category is now kept so a moderator can restore
 *     it later, WITH its article links intact. A hard delete used to drop the
 *     pivot rows with it, which made "restore" impossible.
 *
 *  2. a unique index on `name` — duplicate protection at the database level,
 *     backing up the server-side check in StoreCategoryRequest. Existing
 *     duplicates are MERGED first (articles are moved onto the surviving row,
 *     never dropped), otherwise the index could not be created.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            if (! Schema::hasColumn('tags', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        $this->mergeDuplicateNames();

        Schema::table('tags', function (Blueprint $table) {
            $table->unique('name', 'tags_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique('tags_name_unique');
            $table->dropSoftDeletes();
        });
    }

    /**
     * Fold rows that share a name into the oldest one, re-pointing every
     * article link. Nothing is lost: an article keeps the category, it just
     * points at the surviving row.
     */
    private function mergeDuplicateNames(): void
    {
        $groups = DB::table('tags')
            ->select('name', DB::raw('COUNT(*) as total'))
            ->groupBy('name')
            ->having('total', '>', 1)
            ->pluck('name');

        foreach ($groups as $name) {
            $ids = DB::table('tags')->where('name', $name)->orderBy('id')->pluck('id');
            $keep = $ids->shift();

            foreach ($ids as $duplicate) {
                $links = DB::table('post_tag')->where('tag_id', $duplicate)->pluck('post_id');

                foreach ($links as $postId) {
                    $alreadyLinked = DB::table('post_tag')
                        ->where('tag_id', $keep)
                        ->where('post_id', $postId)
                        ->exists();

                    if ($alreadyLinked) {
                        DB::table('post_tag')->where('tag_id', $duplicate)->where('post_id', $postId)->delete();
                    } else {
                        DB::table('post_tag')->where('tag_id', $duplicate)->where('post_id', $postId)
                            ->update(['tag_id' => $keep]);
                    }
                }

                DB::table('tags')->where('id', $duplicate)->delete();
            }
        }
    }
};
