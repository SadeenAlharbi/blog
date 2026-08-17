<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostService
{
    public function create(User $author, array $data, ?UploadedFile $image = null): Post
    {
        $post = new Post([
            'title' => $data['title'],
            'content' => $data['content'],
            'published_at' => $data['published_at'] ?? now(),
        ]);

        $post->user_id = $author->id;
        $post->slug = $this->uniqueSlug($data['title']);

        if ($image) {
            $post->image = $image->store('posts', 'public');
        }

        $post->save();

        $this->syncTags($post, $data['tags'] ?? []);

        return $post;
    }

    public function update(Post $post, array $data, ?UploadedFile $image = null): Post
    {
        if (array_key_exists('title', $data) && $data['title'] !== $post->title) {
            $post->title = $data['title'];
            $post->slug = $this->uniqueSlug($data['title'], $post->id);
        }

        if (array_key_exists('content', $data)) {
            $post->content = $data['content'];
        }

        if (array_key_exists('published_at', $data)) {
            $post->published_at = $data['published_at'];
        }

        if ($image) {
            if ($post->image) {
                Storage::disk('public')->delete($post->image);
            }
            $post->image = $image->store('posts', 'public');
        }

        $post->save();

        if (array_key_exists('tags', $data)) {
            $this->syncTags($post, $data['tags'] ?? []);
        }

        return $post;
    }

    public function delete(Post $post): void
    {
        if ($post->image) {
            Storage::disk('public')->delete($post->image);
        }

        $post->delete();
    }

    private function syncTags(Post $post, array $tagNames): void
    {
        $tagIds = collect($tagNames)
            ->filter()
            ->map(function (string $name) {
                return Tag::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name]
                )->id;
            });

        $post->tags()->sync($tagIds);
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $i = 1;

        while (
            Post::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$original}-{$i}";
            $i++;
        }

        return $slug;
    }
}
