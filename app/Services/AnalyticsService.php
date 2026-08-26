<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostView;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Every statistic the admin area shows is computed here, straight from the
 * database. Nothing in this class invents a number: if there is no data, the
 * series is a run of real zeroes for the requested days.
 *
 * Date grouping uses DATE(column), which behaves identically on MySQL and on
 * the SQLite database the test suite runs against.
 */
class AnalyticsService
{
    /** Selectable ranges for the dashboard/analytics filters (label => days). */
    public const PERIODS = [
        '7d' => ['label' => 'آخر 7 أيام', 'days' => 7],
        '30d' => ['label' => 'آخر 30 يوم', 'days' => 30],
        '3m' => ['label' => 'آخر 3 أشهر', 'days' => 90],
        '6m' => ['label' => 'آخر 6 أشهر', 'days' => 180],
        '1y' => ['label' => 'هذه السنة', 'days' => 365],
    ];

    public function days(string $period): int
    {
        return self::PERIODS[$period]['days'] ?? self::PERIODS['30d']['days'];
    }

    public function normalizePeriod(?string $period): string
    {
        return array_key_exists($period, self::PERIODS) ? $period : '30d';
    }

    /* ------------------------------------------------------------------ *
     | Overview
     * ------------------------------------------------------------------ */

    public function overview(): array
    {
        return [
            'posts_total' => Post::count(),
            'posts_published' => Post::published()->count(),
            'posts_draft' => Post::draft()->count(),
            'posts_scheduled' => Post::scheduled()->count(),
            'views_total' => PostView::count(),
            'users_total' => User::count(),
            'comments_total' => Comment::count(),
            'comments_hidden' => Comment::hidden()->count(),
        ];
    }

    /* ------------------------------------------------------------------ *
     | Time series — each returns [['date' => 'YYYY-MM-DD', 'value' => n], …]
     * ------------------------------------------------------------------ */

    public function viewsOverTime(int $days): array
    {
        return $this->series(
            PostView::query()->where('created_at', '>=', $this->from($days)),
            'created_at',
            $days
        );
    }

    public function postsOverTime(int $days): array
    {
        return $this->series(
            Post::query()
                ->where('status', Post::STATUS_PUBLISHED)
                ->whereNotNull('published_at')
                ->where('published_at', '>=', $this->from($days))
                ->where('published_at', '<=', now()),
            'published_at',
            $days
        );
    }

    public function commentsOverTime(int $days): array
    {
        return $this->series(
            Comment::query()->where('created_at', '>=', $this->from($days)),
            'created_at',
            $days
        );
    }

    /* ------------------------------------------------------------------ *
     | Rankings
     * ------------------------------------------------------------------ */

    /** Most-read articles. Views are counted over the whole lifetime. */
    public function topPosts(int $limit = 5, ?int $days = null): Collection
    {
        return Post::query()
            ->with(['user', 'tags'])
            ->withCount([
                'views as views_count' => function ($q) use ($days) {
                    if ($days) {
                        $q->where('created_at', '>=', $this->from($days));
                    }
                },
                'comments',
            ])
            ->orderByDesc('views_count')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Categories ranked by how many articles carry them.
     *
     * Uses whereHas rather than HAVING on the withCount alias: HAVING against a
     * select alias without GROUP BY is accepted by MySQL but not portable to the
     * SQLite database the test suite runs on.
     */
    public function topCategories(int $limit = 6): Collection
    {
        return Tag::query()
            ->withCount(['posts' => fn ($q) => $q->where('status', Post::STATUS_PUBLISHED)])
            ->whereHas('posts', fn ($q) => $q->where('status', Post::STATUS_PUBLISHED))
            ->orderByDesc('posts_count')
            ->limit($limit)
            ->get();
    }

    /** Categories ranked by total reads across their articles. */
    public function topCategoriesByViews(int $limit = 6): Collection
    {
        return Tag::query()
            ->select('tags.*')
            ->selectSub(
                PostView::query()
                    ->selectRaw('count(*)')
                    ->join('post_tag', 'post_tag.post_id', '=', 'post_views.post_id')
                    ->whereColumn('post_tag.tag_id', 'tags.id'),
                'views_count'
            )
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();
    }

    /** Authors ranked by published article count (see the note on topCategories). */
    public function topAuthors(int $limit = 5): Collection
    {
        return User::query()
            ->withCount(['posts as published_posts_count' => fn ($q) => $q->where('status', Post::STATUS_PUBLISHED)])
            ->whereHas('posts', fn ($q) => $q->where('status', Post::STATUS_PUBLISHED))
            ->orderByDesc('published_posts_count')
            ->limit($limit)
            ->get();
    }

    /* ------------------------------------------------------------------ *
     | Internals
     * ------------------------------------------------------------------ */

    private function from(int $days): Carbon
    {
        return now()->subDays($days - 1)->startOfDay();
    }

    /**
     * Group a query by day and pad every missing day with a real zero, so a
     * chart always spans the full requested range.
     */
    private function series($query, string $column, int $days): array
    {
        $counts = $query
            ->selectRaw("DATE({$column}) as day, COUNT(*) as aggregate")
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $out[] = [
                'date' => $date,
                'value' => (int) ($counts[$date] ?? 0),
            ];
        }

        return $out;
    }
}
