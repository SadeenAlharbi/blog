<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(private readonly PostService $posts)
    {
    }

    public function index(Request $request)
    {
        $query = Post::query()
            ->with(['user', 'tags'])
            ->withCount('comments');

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhereHas('tags', function ($tagQuery) use ($search) {
                        $tagQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($tag = $request->string('tag')->trim()->value()) {
            $query->whereHas('tags', function ($tagQuery) use ($tag) {
                $tagQuery->where('slug', $tag)->orWhere('name', $tag);
            });
        }

        $sort = $request->string('sort')->value() ?: 'latest';
        match ($sort) {
            'oldest' => $query->oldest('published_at'),
            'title' => $query->orderBy('title'),
            default => $query->latest('published_at'),
        };

        $posts = $query->paginate($request->integer('per_page', 10))->withQueryString();

        return PostResource::collection($posts);
    }

    public function store(StorePostRequest $request)
    {
        $post = $this->posts->create(
            $request->user(),
            $request->validated(),
            $request->file('image')
        );

        $post->load(['user', 'tags']);

        return response()->json([
            'data' => new PostResource($post),
            'message' => 'Post created successfully.',
        ], 201);
    }

    public function show(Post $post)
    {
        $post->load(['user', 'tags', 'comments.user']);

        return response()->json([
            'data' => new PostResource($post),
            'message' => 'OK',
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        $post = $this->posts->update($post, $request->validated(), $request->file('image'));
        $post->load(['user', 'tags']);

        return response()->json([
            'data' => new PostResource($post),
            'message' => 'Post updated successfully.',
        ]);
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $this->posts->delete($post);

        return response()->json([
            'data' => null,
            'message' => 'Post deleted successfully.',
        ]);
    }
}
