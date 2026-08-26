<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostView;
use Illuminate\Http\Request;

/**
 * Records article reads, with de-duplication so a refresh (or a reader
 * scrolling back and forth) does not inflate the counter.
 *
 * Identity: a signed-in reader is identified by user_id, a guest by a SHA-256
 * hash of their IP (the raw address is never stored). A repeat read by the
 * same identity inside DEDUPE_MINUTES is ignored.
 */
class PostViewService
{
    /** A second read by the same visitor within this window is not counted. */
    public const DEDUPE_MINUTES = 30;

    public function record(Post $post, Request $request): bool
    {
        // Only published articles accumulate public view counts; an author
        // previewing their own draft must not inflate its statistics.
        if (! $post->isPublished()) {
            return false;
        }

        $userId = $request->user()?->id;
        $ipHash = $this->hashIp($request->ip());

        if ($this->seenRecently($post, $userId, $ipHash)) {
            return false;
        }

        PostView::create([
            'post_id' => $post->id,
            'user_id' => $userId,
            'ip_hash' => $ipHash,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255) ?: null,
        ]);

        return true;
    }

    private function seenRecently(Post $post, ?int $userId, ?string $ipHash): bool
    {
        $since = now()->subMinutes(self::DEDUPE_MINUTES);

        return PostView::query()
            ->where('post_id', $post->id)
            ->where('created_at', '>=', $since)
            ->when(
                $userId,
                fn ($q) => $q->where('user_id', $userId),
                fn ($q) => $q->whereNull('user_id')->where('ip_hash', $ipHash),
            )
            ->exists();
    }

    private function hashIp(?string $ip): ?string
    {
        return $ip ? hash('sha256', $ip.config('app.key')) : null;
    }
}
