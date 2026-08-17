<?php

namespace App\Providers;

use App\Models\Post;
use App\Policies\PostPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        RateLimiter::for('comments', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()->id);
        });

        RateLimiter::for('posts', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()->id);
        });

        RateLimiter::for('tags', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()->id);
        });

        RateLimiter::for('login', function (Request $request) {
            $key = Str::lower($request->string('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });
    }
}
