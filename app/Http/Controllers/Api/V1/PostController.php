<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use App\Services\PostViewService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(
        private readonly PostService $posts,
        private readonly PostViewService $views,
    ) {
    }

    public function index(Request $request)
    {
        $query = Post::query()
            ->with(['user', 'tags'])
            ->withCount(['comments', 'views']);

        // Published-only by default; an authenticated admin may widen it.
        $publishedOnly = $this->applyVisibility($query, $request);

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

        /*
         * Drafts have a NULL published_at, so ordering an unpublished listing
         * by that column leaves the order undefined and pagination unstable.
         * Fall back to created_at whenever the listing is not published-only.
         */
        $sort = $request->string('sort')->value() ?: 'latest';
        $dateColumn = $publishedOnly ? 'published_at' : 'created_at';

        match ($sort) {
            'oldest' => $query->oldest($dateColumn),
            'title' => $query->orderBy('title'),
            'views' => $query->orderByDesc('views_count'),
            default => $query->latest($dateColumn),
        };

        $posts = $query->paginate($request->integer('per_page', 10))->withQueryString();

        return PostResource::collection($posts);
    }

    /**
     * Restrict the listing to what the caller is allowed to see.
     *
     * The default is unchanged: published articles only. An ADMIN who is
     * authenticated may narrow to a specific status with `?status=draft`
     * (or widen with `?status=all`) — the same rule show() already applies
     * to a single article, where an admin may read an unpublished one. The
     * listing was simply stricter than the detail endpoint for no reason.
     *
     * Non-breaking: a request without `?status` behaves exactly as before,
     * so every existing consumer keeps working.
     *
     * Fails safe: anything unrecognised — an unknown status, a guest, a
     * non-admin — falls through to published() rather than widening.
     *
     * @return bool whether the listing was restricted to published articles
     */
    private function applyVisibility($query, Request $request): bool
    {
        $status = $request->string('status')->trim()->value();
        $allowed = [...array_keys(Post::statuses()), 'all'];

        /*
         * This is a PUBLIC route: it deliberately sits outside the
         * auth:sanctum group so guests can read articles. The consequence is
         * that $request->user() consults only the DEFAULT guard (session), so
         * a perfectly valid Bearer token resolves to nobody and the admin
         * check below fails silently. Ask the sanctum guard explicitly.
         *
         * Both callers are covered: an admin browsing the site (session) and
         * the articles-manager calling the API (token).
         */
        $user = $request->user() ?? $request->user('sanctum');

        if ($status === ''
            || ! in_array($status, $allowed, true)
            || ! $user?->isAdmin()) {
            $query->published();

            return true;
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return false;
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

    public function show(Request $request, Post $post)
    {
        // Unpublished articles are readable only by their author or an admin.
        if (! $post->isPublished()) {
            $user = $request->user();

            if (! $user || (! $user->isAdmin() && $user->id !== $post->user_id)) {
                abort(404);
            }
        }

        $this->views->record($post, $request);

        $post->load(['user', 'tags', 'comments' => fn ($q) => $q->approved()->with('user')]);
        $post->loadCount('views');

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
