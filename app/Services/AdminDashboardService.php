<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostView;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Assembles the admin dashboard. The controller calls these methods and hands
 * the result to the view — it holds no queries of its own.
 *
 * Every figure comes from the database. Where a "change" percentage is shown,
 * it compares the current window with the immediately preceding window of the
 * same length, and is returned as null when there is no prior data to compare
 * against (rather than fabricating a trend).
 */
class AdminDashboardService
{
    public function __construct(private readonly AnalyticsService $analytics)
    {
    }

    /* ------------------------------------------------------------------ *
     | KPI cards
     * ------------------------------------------------------------------ */

    public function getOverviewStats(int $days = 30): array
    {
        $base = $this->analytics->overview();

        return array_merge($base, [
            'posts_change' => $this->change(
                Post::query()->where('created_at', '>=', now()->subDays($days)),
                Post::query()->whereBetween('created_at', [now()->subDays($days * 2), now()->subDays($days)])
            ),
            'views_change' => $this->change(
                PostView::query()->where('created_at', '>=', now()->subDays($days)),
                PostView::query()->whereBetween('created_at', [now()->subDays($days * 2), now()->subDays($days)])
            ),
            'users_change' => $this->change(
                User::query()->where('created_at', '>=', now()->subDays($days)),
                User::query()->whereBetween('created_at', [now()->subDays($days * 2), now()->subDays($days)])
            ),
            'comments_change' => $this->change(
                Comment::query()->where('created_at', '>=', now()->subDays($days)),
                Comment::query()->whereBetween('created_at', [now()->subDays($days * 2), now()->subDays($days)])
            ),
        ]);
    }

    /* ------------------------------------------------------------------ *
     | Charts
     * ------------------------------------------------------------------ */

    /** Three aligned daily series for the content-growth chart. */
    public function getContentGrowth(int $days = 30): array
    {
        return [
            'posts' => $this->analytics->postsOverTime($days),
            'views' => $this->analytics->viewsOverTime($days),
            'comments' => $this->analytics->commentsOverTime($days),
        ];
    }

    public function getTopCategories(int $limit = 6): Collection
    {
        return $this->analytics->topCategories($limit);
    }

    public function getMostViewedPosts(int $limit = 5): Collection
    {
        return $this->analytics->topPosts($limit);
    }

    public function getTopAuthors(int $limit = 5): Collection
    {
        return $this->analytics->topAuthors($limit);
    }

    /* ------------------------------------------------------------------ *
     | Lists
     * ------------------------------------------------------------------ */

    public function getLatestPosts(int $limit = 6): Collection
    {
        return Post::query()
            ->with(['user', 'tags'])
            ->withCount(['views', 'comments'])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function getLatestComments(int $limit = 5): Collection
    {
        return Comment::query()
            ->with(['user', 'post'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * "Needs your attention" — each entry is a real count with a link to the
     * filtered screen that resolves it. Entries with a zero count are dropped
     * by the view, so the widget never shows made-up work.
     *
     * Pass the stats array from getOverviewStats() to reuse the three figures
     * it already counted (drafts, scheduled, hidden comments) instead of
     * running those same three COUNT queries a second time. The numbers are
     * identical either way — they come from the same scopes.
     */
    public function getNeedsAttention(array $stats = []): array
    {
        return [
            [
                'key' => 'drafts',
                'label' => 'مقالات في المسودة',
                'count' => $stats['posts_draft'] ?? Post::draft()->count(),
                'url' => route('admin.posts.index', ['status' => Post::STATUS_DRAFT]),
            ],
            [
                'key' => 'scheduled',
                'label' => 'مقالات مجدولة للنشر',
                'count' => $stats['posts_scheduled'] ?? Post::scheduled()->count(),
                'url' => route('admin.posts.index', ['status' => Post::STATUS_SCHEDULED]),
            ],
            [
                'key' => 'no_category',
                'label' => 'مقالات بدون تصنيف',
                'count' => Post::query()->whereDoesntHave('tags')->count(),
                'url' => route('admin.posts.index', ['missing' => 'category']),
            ],
            [
                'key' => 'no_image',
                'label' => 'مقالات بدون صورة',
                'count' => Post::query()->whereNull('image')->count(),
                'url' => route('admin.posts.index', ['missing' => 'image']),
            ],
            [
                'key' => 'hidden_comments',
                'label' => 'تعليقات مخفية',
                'count' => $stats['comments_hidden'] ?? Comment::hidden()->count(),
                'url' => route('admin.comments.index', ['status' => Comment::STATUS_HIDDEN]),
            ],
        ];
    }

    /**
     * Recent activity, derived from data that already exists (new posts, new
     * comments, new accounts) rather than from a separate audit table. Each
     * row is a real record with its real timestamp.
     */
    public function getRecentActivity(int $limit = 8): Collection
    {
        $posts = Post::query()->with('user')->latest('created_at')->limit($limit)->get()
            ->map(fn (Post $p) => [
                'type' => 'post',
                'icon' => 'document',
                'text' => 'نشر مقال: '.$p->title,
                'actor' => $p->user?->name,
                'at' => $p->created_at,
                'url' => route('admin.posts.edit', $p),
            ]);

        $comments = Comment::query()->with(['user', 'post'])->latest()->limit($limit)->get()
            ->map(fn (Comment $c) => [
                'type' => 'comment',
                'icon' => 'chat',
                'text' => 'تعليق جديد على: '.($c->post?->title ?? 'مقال محذوف'),
                'actor' => $c->user?->name,
                'at' => $c->created_at,
                'url' => route('admin.comments.index'),
            ]);

        $users = User::query()->latest()->limit($limit)->get()
            ->map(fn (User $u) => [
                'type' => 'user',
                'icon' => 'user',
                'text' => 'انضم مستخدم جديد',
                'actor' => $u->name,
                'at' => $u->created_at,
                'url' => route('admin.users.index'),
            ]);

        return $posts->concat($comments)->concat($users)
            ->filter(fn ($row) => $row['at'] !== null)
            ->sortByDesc('at')
            ->take($limit)
            ->values();
    }

    /* ------------------------------------------------------------------ *
     | Internals
     * ------------------------------------------------------------------ */

    /** Percentage change, or null when the previous window holds no data. */
    private function change($currentQuery, $previousQuery): ?float
    {
        $previous = (clone $previousQuery)->count();

        if ($previous === 0) {
            return null;
        }

        $current = (clone $currentQuery)->count();

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
