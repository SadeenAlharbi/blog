<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\TagController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:register');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{post:slug}', [PostController::class, 'show']);
    Route::get('/posts/{post:slug}/comments', [CommentController::class, 'index']);

    Route::get('/tags', [TagController::class, 'index'])
        ->middleware('throttle:tags-index');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])
            ->middleware('throttle:logout');
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

        Route::post('/tags', [TagController::class, 'store'])
            ->middleware('throttle:tags');
    });
});
