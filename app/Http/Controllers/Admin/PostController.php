<?php

namespace App\Http\Controllers\Admin;

use App\Events\PostModeratedByAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Http\Request;

/**
 * Full article administration. Authorization is always checked against
 * PostPolicy (an admin passes it for any post), never assumed from the route.
 */
class PostController extends Controller
{
    public function __construct(private readonly PostService $posts)
    {
    }

    public function index(Request $request)
    {
        $query = Post::query()
            ->with(['user', 'tags'])
            ->withCount(['comments', 'views']);

        // Removed articles are hidden by default and listed on request, so a
        // moderator can review or restore what was deleted.
        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->trim()->value()) {
            if (array_key_exists($status, Post::statuses())) {
                $query->where('status', $status);
            }
        }

        if ($tag = $request->string('tag')->trim()->value()) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $tag));
        }

        if ($author = $request->integer('author')) {
            $query->where('user_id', $author);
        }

        // Shortcuts used by the "needs attention" widget.
        match ($request->string('missing')->trim()->value()) {
            'category' => $query->whereDoesntHave('tags'),
            'image' => $query->whereNull('image'),
            default => null,
        };

        $sort = $request->string('sort')->value() ?: 'latest';
        match ($sort) {
            'oldest' => $query->oldest('created_at'),
            'views' => $query->orderByDesc('views_count'),
            'comments' => $query->orderByDesc('comments_count'),
            'title' => $query->orderBy('title'),
            default => $query->latest('created_at'),
        };

        return view('admin.posts.index', [
            'posts' => $query->paginate(15)->withQueryString(),
            'statuses' => Post::statuses(),
            'categories' => Tag::orderBy('name')->get(),
            'authors' => User::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'tag', 'author', 'sort', 'missing', 'trashed']),
            'trashedCount' => Post::onlyTrashed()->count(),
        ]);
    }

    /**
     * Article detail INSIDE the dashboard, so a moderator who opens an article
     * from the list stays in the admin context and has a way back.
     */
    public function show(Post $post)
    {
        $post->load(['user', 'tags', 'comments' => fn ($q) => $q->with('user')->latest()]);
        $post->loadCount('views');

        return view('admin.posts.show', compact('post'));
    }

    public function create()
    {
        return view('admin.posts.create', [
            'categories' => Tag::options(),
            'statuses' => Post::statuses(),
        ]);
    }

    public function store(StorePostRequest $request)
    {
        $post = $this->posts->create(
            $request->user(),
            $request->validated(),
            $request->file('image')
        );

        return redirect()
            ->route('admin.posts.index')
            ->with('success', 'تم حفظ المقال بنجاح.');
    }

    public function edit(Post $post)
    {
        $this->authorize('update', $post);

        $post->load('tags');

        return view('admin.posts.edit', [
            'post' => $post,
            'categories' => Tag::options(),
            'statuses' => Post::statuses(),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        $this->posts->update($post, $request->validated(), $request->file('image'));

        // Tell the author their article was edited by a moderator. The listener
        // stays silent when the moderator IS the author.
        PostModeratedByAdmin::dispatch($post, $request->user(), PostModeratedByAdmin::ACTION_UPDATED);

        return redirect()
            ->route('admin.posts.index')
            ->with('success', 'تم تحديث المقال بنجاح.');
    }

    public function destroy(Request $request, Post $post)
    {
        $this->authorize('delete', $post);

        // Dispatch BEFORE deleting, while the relations are still loadable.
        PostModeratedByAdmin::dispatch($post, $request->user(), PostModeratedByAdmin::ACTION_DELETED);

        // Soft delete — the row survives so readers get "removed by the
        // moderators" instead of a 404, and the article can be restored.
        $this->posts->delete($post);

        return redirect()
            ->route('admin.posts.index')
            ->with('success', 'تم حذف المقال.');
    }

    /** Restore a soft-deleted article. */
    public function restore(int $post)
    {
        $model = Post::withTrashed()->findOrFail($post);

        $this->authorize('delete', $model);

        $model->restore();

        return back()->with('success', 'تمت استعادة المقال.');
    }

    /* ------------------------------------------------------------------ *
     | Publication state
     * ------------------------------------------------------------------ */

    public function publish(Post $post)
    {
        $this->authorize('publish', $post);

        $this->posts->publish($post);

        return back()->with('success', 'تم نشر المقال.');
    }

    public function unpublish(Post $post)
    {
        $this->authorize('publish', $post);

        $this->posts->unpublish($post);

        return back()->with('success', 'تم تحويل المقال إلى مسودة.');
    }

    public function schedule(Request $request, Post $post)
    {
        $this->authorize('publish', $post);

        $validated = $request->validate(
            ['published_at' => ['required', 'date', 'after:now']],
            [
                'published_at.required' => 'تاريخ الجدولة مطلوب.',
                'published_at.date' => 'تاريخ الجدولة غير صالح.',
                'published_at.after' => 'يجب أن يكون تاريخ الجدولة في المستقبل.',
            ]
        );

        $this->posts->schedule($post, $validated['published_at']);

        return back()->with('success', 'تمت جدولة المقال للنشر.');
    }

    public function cancelSchedule(Post $post)
    {
        $this->authorize('publish', $post);

        $this->posts->cancelSchedule($post);

        return back()->with('success', 'تم إلغاء الجدولة، المقال الآن مسودة.');
    }
}
