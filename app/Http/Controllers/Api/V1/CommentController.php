<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Notifications\NewCommentNotification;

class CommentController extends Controller
{
    public function index(Post $post)
    {
        $comments = $post->comments()
            ->approved()
            ->with('user')
            ->latest()
            ->paginate(15);

        return CommentResource::collection($comments);
    }

    public function store(StoreCommentRequest $request, Post $post)
    {
        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->validated('content'),
        ]);

        $comment->load('user');

        if ($post->user_id !== $request->user()->id) {
            $post->user->notify(new NewCommentNotification($comment));
        }

        return response()->json([
            'data' => new CommentResource($comment),
            'message' => 'Comment added successfully.',
        ], 201);
    }

    /**
     * Delete a comment. Mirrors the web behaviour exactly: the comment's own
     * author may delete it, and so may an administrator — enforced by
     * CommentPolicy, so the API cannot be used to bypass the web rules.
     */
    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'data' => null,
            'message' => 'Comment deleted successfully.',
        ]);
    }
}
