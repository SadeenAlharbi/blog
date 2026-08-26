<?php

use App\Models\Comment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The platform has no comment review queue: a comment is public as soon as it
 * is written, and moderation happens afterwards (hide / delete).
 *
 * Any comment left in the retired 'pending' state would otherwise be invisible
 * to the public AND unreachable from the admin filters, so it is released to
 * the normal visible state. The `status` column itself stays — it still carries
 * approved | hidden.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('comments')
            ->where('status', 'pending')
            ->update(['status' => Comment::STATUS_APPROVED]);
    }

    public function down(): void
    {
        // Irreversible by design: which comments were "pending" is not recorded
        // anywhere, and re-hiding approved comments would be destructive.
    }
};
