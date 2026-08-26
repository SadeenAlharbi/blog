<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Models\Tag;
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
        // Only published articles are listed publicly — drafts and scheduled
        // posts never appear here, whoever is browsing.
        $query = Post::query()
            ->published()
            ->with(['user', 'tags'])
            ->withCount('comments');

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($tag = $request->string('tag')->trim()->value()) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $tag));
        }

        $posts = $query->latest('published_at')->paginate(9)->withQueryString();
        $tags = Tag::orderBy('name')->get();

        return view('posts.index', compact('posts', 'tags'));
    }

    public function show(Request $request, Post $post)
    {
        // A draft or scheduled article is visible only to its author or an
        // administrator; everyone else gets a 404, not a hidden 403 hint.
        if (! $post->isPublished()) {
            $user = $request->user();

            if (! $user || (! $user->isAdmin() && $user->id !== $post->user_id)) {
                abort(404);
            }
        }

        // Record the read (de-duplicated inside the service).
        $this->views->record($post, $request);

        $post->load(['user', 'tags', 'comments' => fn ($q) => $q->approved()->with('user')]);
        $post->loadCount('views');

        return view('posts.show', compact('post'));
    }

    /**
     * Lightweight liveness check for an open article page.
     *
     * The reading page polls this so that, if a moderator removes the article
     * or one of its comments while someone is sitting on it, the page can say
     * so in place instead of the reader discovering it via a broken refresh.
     * Returns the ids still visible; the page removes anything missing.
     */
    public function availability(string $slug)
    {
        $post = Post::withTrashed()->where('slug', $slug)->first();

        if (! $post || $post->trashed() || ! $post->isPublished()) {
            return response()->json([
                'available' => false,
                'reason' => $post && $post->trashed() ? 'deleted' : 'unavailable',
                'comments' => [],
            ]);
        }

        return response()->json([
            'available' => true,
            'reason' => null,
            'comments' => $post->comments()->approved()->pluck('id'),
        ]);
    }

    public function create()
    {
        $tags = Tag::orderBy('name')->get();

        return view('posts.create', compact('tags'));
    }

    public function store(StorePostRequest $request)
    {
        $post = $this->posts->create(
            $request->user(),
            $request->validated(),
            $request->file('image')
        );

        // A draft belongs in the author's own dashboard, not on a public URL.
        if ($post->isDraft()) {
            return redirect()->route('dashboard')->with('success', 'تم حفظ المقال كمسودة. يمكنك نشره في أي وقت.');
        }

        return redirect()->route('posts.show', $post)
            ->with('success', $post->isScheduled() ? 'تمت جدولة المقال للنشر.' : 'تم نشر المقال بنجاح.');
    }

    public function edit(Post $post)
    {
        $this->authorize('update', $post);

        $tags = Tag::orderBy('name')->get();

        return view('posts.edit', compact('post', 'tags'));
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        $this->posts->update($post, $request->validated(), $request->file('image'));

        if ($post->isDraft()) {
            return redirect()->route('dashboard')->with('success', 'تم حفظ المقال كمسودة.');
        }

        return redirect()->route('posts.show', $post)->with('success', 'تم تحديث المقال بنجاح.');
    }

    /**
     * Publish one of your own drafts straight from the dashboard.
     *
     * Same PostService path the admin area and the edit form use, so the
     * status/date pair stays consistent and moderators still get the
     * "a member published something" notification.
     */
    public function publishOwn(Post $post)
    {
        $this->authorize('update', $post);

        if ($post->isPublished()) {
            return back()->with('success', 'المقال منشور بالفعل.');
        }

        $this->posts->publish($post);

        return redirect()->route('posts.show', $post)->with('success', 'تم نشر المقال بنجاح.');
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $this->posts->delete($post);

        return redirect()->route('dashboard')->with('success', 'تم حذف المقال.');
    }
}
