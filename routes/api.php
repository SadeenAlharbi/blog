<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API v1
|--------------------------------------------------------------------------
| Two independent layers guard this surface:
|
|   apikey  — identifies the CLIENT APPLICATION via the X-API-KEY header.
|             Inactive until API_KEY is set in .env (see config/services.php).
|   sanctum — identifies the USER via `Authorization: Bearer <token>`.
|
| Authorization itself (who may edit what) is always a Policy, so the API and
| the web app can never disagree. `active` additionally stops a disabled
| account from using a token issued before it was disabled.
|
| Canonical auth paths are /auth/*. The original un-prefixed paths are kept as
| aliases so existing API consumers do not break.
*/

Route::prefix('v1')->middleware('apikey')->group(function () {

    /* ----------------------------- Authentication ---------------------------- */

    Route::post('/auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:register')->name('api.auth.register');
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')->name('api.auth.login');

    // Backwards-compatible aliases (original paths).
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    /* -------------------------------- Public -------------------------------- */

    Route::middleware('throttle:posts-read')->group(function () {
        Route::get('/posts', [PostController::class, 'index']);
        Route::get('/posts/{post:slug}', [PostController::class, 'show']);
    });

    Route::get('/posts/{post:slug}/comments', [CommentController::class, 'index'])
        ->middleware('throttle:comments-index');

    Route::get('/tags', [TagController::class, 'index'])
        ->middleware('throttle:tags-index');

    /* ------------------------------ Authenticated ---------------------------- */

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->middleware('throttle:logout')->name('api.auth.logout');
        Route::get('/auth/me', [AuthController::class, 'user'])->name('api.auth.me');

        // Backwards-compatible aliases.
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:logout');
        Route::get('/user', [AuthController::class, 'user']);

        Route::post('/posts', [PostController::class, 'store'])
            ->middleware('throttle:posts');

        Route::middleware('throttle:post-mutations')->group(function () {
            Route::put('/posts/{post:slug}', [PostController::class, 'update']);
            Route::patch('/posts/{post:slug}', [PostController::class, 'update']);
            Route::delete('/posts/{post:slug}', [PostController::class, 'destroy']);
        });

        Route::post('/posts/{post:slug}/comments', [CommentController::class, 'store'])
            ->middleware('throttle:comments');

        // Parity with the web app: a comment's author (or an admin) may delete
        // it. Authorization is CommentPolicy, exactly as on the site.
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
            ->middleware('throttle:comments');

        // Notifications for the signed-in user (the same records the bell reads).
        Route::middleware('throttle:notifications')->group(function () {
            Route::get('/notifications', [NotificationController::class, 'index']);
            Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
            Route::patch('/notifications/read-all', [NotificationController::class, 'readAll']);
            Route::post('/notifications/{notification}/read', [NotificationController::class, 'read']);
            Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read']);
        });

        Route::post('/tags', [TagController::class, 'store'])
            ->middleware('throttle:tags');
    });
});
