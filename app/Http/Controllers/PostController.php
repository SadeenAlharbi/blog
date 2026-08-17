<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Models\Tag;
use App\Services\PostService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(private readonly PostService $posts)
    {
    }

    public function index(Request $request)
    {
        $query = Post::query()->with(['user', 'tags'])->withCount('comments');

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

    public function show(Post $post)
    {
        $post->load(['user', 'tags', 'comments.user']);

        return view('posts.show', compact('post'));
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

        return redirect()->route('posts.show', $post)->with('success', 'تم نشر المقال بنجاح.');
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

        return redirect()->route('posts.show', $post)->with('success', 'تم تحديث المقال بنجاح.');
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $this->posts->delete($post);

        return redirect()->route('dashboard')->with('success', 'تم حذف المقال.');
    }
}
