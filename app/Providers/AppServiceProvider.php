<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Policies\CommentPolicy;
use App\Policies\PostPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // Convenience gate for views: @can('access-admin').
        Gate::define('access-admin', fn (User $user) => $user->isAdmin() && $user->is_active);

        /*
         * Moderation events → notifications.
         *
         * The wiring is NOT repeated here on purpose: Laravel already discovers
         * every listener in app/Listeners from the event type-hinted on its
         * handle() method. Registering them again in this provider subscribed
         * each listener twice and sent the author two identical notifications
         * for one action.
         *
         *   App\Events\PostModeratedByAdmin    → App\Listeners\NotifyPostAuthorOfModeration
         *   App\Events\CommentModeratedByAdmin → App\Listeners\NotifyCommentAuthorOfModeration
         *   App\Events\UserAccountChangedByAdmin → App\Listeners\NotifyUserOfAccountChange
         *
         * Controllers only dispatch the event; who gets notified, and with what
         * wording, lives in the listener and the notification class.
         */

        /*
         * Footer lists — real articles from the database, not a fixed menu.
         * Two titles each, and nothing else: no view counts, no dates. The
         * footer renders on every page, so the two small queries are cached
         * briefly rather than run per request.
         */
        View::composer('partials.footer', function ($view) {
            [$mostRead, $latest] = Cache::remember('footer.lists', now()->addMinutes(10), function () {
                $mostRead = Post::query()
                    ->published()
                    ->withCount('views')
                    ->orderByDesc('views_count')
                    ->limit(2)
                    ->get()
                    ->map(fn (Post $p) => [
                        'slug' => $p->slug,
                        'title' => Str::limit($p->title, 46),
                    ])->all();

                // Drafts and scheduled articles are excluded by published().
                $latest = Post::query()
                    ->published()
                    ->latest('published_at')
                    ->limit(2)
                    ->get()
                    ->map(fn (Post $p) => [
                        'slug' => $p->slug,
                        'title' => Str::limit($p->title, 46),
                    ])->all();

                return [$mostRead, $latest];
            });

            $view->with('footerMostRead', $mostRead)->with('footerLatest', $latest);
        });

        RateLimiter::for('comments', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()->id);
        });

        RateLimiter::for('posts', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()->id);
        });

        RateLimiter::for('tags', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()->id);
        });

        RateLimiter::for('post-mutations', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()->id);
        });

        RateLimiter::for('login', function (Request $request) {
            $key = Str::lower($request->string('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('logout', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()->id);
        });

        RateLimiter::for('tags-index', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('posts-read', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('comments-index', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Notification endpoints are polled by the bell — a higher ceiling.
        RateLimiter::for('notifications', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Admin write actions.
        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
