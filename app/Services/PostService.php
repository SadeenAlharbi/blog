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

    /**
     * Attach tags from the fixed category list only. Accepts either canonical
     * slugs (from the web form's checkboxes) or canonical Arabic names (API),
     * plus any already-existing tag matched by slug/name. Anything else is
     * ignored — no arbitrary tag creation, so junk tags can't be introduced.
     */
    private function syncTags(Post $post, array $values): void
    {
        $categories = Tag::categories(); // [slug => name]

        $tagIds = collect($values)
            ->map(fn ($v) => is_string($v) ? trim($v) : $v)
            ->filter()
            ->map(function ($value) use ($categories) {
                if (isset($categories[$value])) {
                    return Tag::firstOrCreate(['slug' => $value], ['name' => $categories[$value]])->id;
                }

                $slug = array_search($value, $categories, true);
                if ($slug !== false) {
                    return Tag::firstOrCreate(['slug' => $slug], ['name' => $value])->id;
                }

                return Tag::query()
                    ->where('slug', $value)
                    ->orWhere('name', $value)
                    ->value('id');
            })
            ->filter()
            ->unique()
            ->values();

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
