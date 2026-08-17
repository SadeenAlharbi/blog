<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Post;
use App\Notifications\NewCommentNotification;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Post $post)
    {
        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->validated('content'),
        ]);

        if ($post->user_id !== $request->user()->id) {
            $post->user->notify(new NewCommentNotification($comment));
        }

        return redirect()->route('posts.show', $post)->with('success', 'تمت إضافة تعليقك بنجاح.');
    }
}
