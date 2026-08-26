<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-view records for articles, used by the dashboard/analytics counters.
 *
 * The visitor IP is stored HASHED (sha256, never the raw address) so the table
 * can de-duplicate repeated hits without keeping personal data. A signed-in
 * visitor is de-duplicated by user_id instead. Indexes match exactly the three
 * lookups the app performs: counting per post, and the two dedupe probes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['post_id', 'created_at']);
            $table->index(['post_id', 'user_id', 'created_at']);
            $table->index(['post_id', 'ip_hash', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_views');
    }
};
