<?php

namespace App\Console\Commands;

use App\Services\PostService;
use Illuminate\Console\Command;

/**
 * Releases scheduled articles whose publish moment has passed.
 *
 * Scheduled posts are ALSO safe without this command: the `published()` scope
 * hides future-dated posts and shows them once the date passes. This command
 * simply keeps the stored status column truthful (and the admin counters
 * accurate) — run it from the scheduler.
 */
class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled';

    protected $description = 'نشر المقالات المجدولة التي حان موعد نشرها';

    public function handle(PostService $posts): int
    {
        $count = $posts->releaseDueScheduled();

        $this->info($count > 0
            ? "تم نشر {$count} مقالاً مجدولاً."
            : 'لا توجد مقالات مجدولة مستحقة للنشر.');

        return self::SUCCESS;
    }
}
