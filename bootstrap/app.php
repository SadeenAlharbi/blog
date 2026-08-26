<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\VerifyApiKey;
use App\Models\Post;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            // Gate for the whole /admin area and the admin API group.
            'admin' => EnsureUserIsAdmin::class,
            // Blocks a session or token belonging to a disabled account.
            'active' => EnsureAccountIsActive::class,
            // Identifies the calling client application (X-API-KEY).
            'apikey' => VerifyApiKey::class,
        ]);

        // A disabled account must not keep browsing on an existing session.
        $middleware->appendToGroup('web', EnsureAccountIsActive::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // The upload exceeded PHP's post_max_size and was rejected before Laravel
        // validation could run. Show a friendly, RTL message instead of the raw
        // 413 stack trace. (The post form also blocks oversize files client-side.)
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'حجم البيانات المُرسلة كبير جداً. يرجى رفع صورة أصغر (بحد أقصى 5 ميجابايت).',
                ], 413);
            }

            return response()->view('errors.413', [], 413);
        });

        /*
         * An article that a moderator removed must never read as a dead link.
         * Route-model binding excludes soft-deleted rows, so the request lands
         * here as a 404 — if the slug belongs to a removed article we send the
         * reader to the articles list with an explanation instead of a 404 page.
         */
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            // Both the reader's URL (/posts/{slug}) and the API's
            // (/api/v1/posts/{slug}) route to the same explanation.
            $isArticleUrl = $request->is('posts/*') || $request->is('api/*/posts/*');

            if (! $request->isMethod('GET') || ! $isArticleUrl) {
                return null;
            }

            $slug = basename($request->path());

            if (! Post::onlyTrashed()->where('slug', $slug)->exists()) {
                return null;
            }

            $message = 'تم حذف هذا المقال من قبل الإدارة.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 410);
            }

            return redirect()
                ->route('posts.index')
                ->with('removed_notice', $message);
        });
    })->create();
