<?php

namespace App\Services;

use App\Events\PostPublished;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostService
{
    public function create(User $author, array $data, ?UploadedFile $image = null): Post
    {
        $post = new Post([
            'title' => $data['title'],
            'content' => $data['content'],
        ]);

        [$status, $publishedAt] = $this->resolvePublishing(
            $data['status'] ?? null,
            $data['published_at'] ?? null
        );

        $post->status = $status;
        $post->published_at = $publishedAt;
        $post->user_id = $author->id;
        $post->slug = $this->uniqueSlug($data['slug'] ?? $data['title']);

        if ($image) {
            $post->image = $image->store('posts', 'public');
        }

        $post->save();

        $this->syncTags($post, $data['tags'] ?? []);

        // A new article that is public right away is a publish transition.
        $this->announceIfPublished($post, false);

        return $post;
    }

    public function update(Post $post, array $data, ?UploadedFile $image = null): Post
    {
        $wasPublished = $post->isPublished();

        if (array_key_exists('title', $data) && $data['title'] !== $post->title) {
            $post->title = $data['title'];

            // Only regenerate the slug when the editor did not supply one.
            if (! array_key_exists('slug', $data) || blank($data['slug'])) {
                $post->slug = $this->uniqueSlug($data['title'], $post->id);
            }
        }

        if (array_key_exists('slug', $data) && filled($data['slug'])) {
            $post->slug = $this->uniqueSlug($data['slug'], $post->id);
        }

        if (array_key_exists('content', $data)) {
            $post->content = $data['content'];
        }

        // Publication state is only touched when the form actually sent it, so
        // an edit that omits these fields leaves the schedule untouched.
        if (array_key_exists('status', $data) || array_key_exists('published_at', $data)) {
            [$status, $publishedAt] = $this->resolvePublishing(
                $data['status'] ?? $post->status,
                $data['published_at'] ?? $post->published_at,
            );

            $post->status = $status;
            $post->published_at = $publishedAt;
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

        $this->announceIfPublished($post, $wasPublished);

        return $post;
    }

    /**
     * Fire PostPublished exactly once per transition into the published state.
     * Editing an already-public article announces nothing.
     */
    private function announceIfPublished(Post $post, bool $wasPublished): void
    {
        if (! $wasPublished && $post->isPublished()) {
            PostPublished::dispatch($post);
        }
    }

    /**
     * Soft-delete an article.
     *
     * The cover image is deliberately KEPT: the row still exists (SoftDeletes)
     * and can be restored, so removing the file would leave a restored article
     * with a broken image. Use forceDelete() for permanent removal.
     */
    public function delete(Post $post): void
    {
        $post->delete();
    }

    /** Permanently remove an article and its stored cover image. */
    public function forceDelete(Post $post): void
    {
        if ($post->image) {
            Storage::disk('public')->delete($post->image);
        }

        $post->forceDelete();
    }

    /* ------------------------------------------------------------------ *
     | Publishing workflow
     * ------------------------------------------------------------------ */

    /** Publish immediately (also cancels a pending schedule). */
    public function publish(Post $post): Post
    {
        $wasPublished = $post->isPublished();

        $post->status = Post::STATUS_PUBLISHED;
        $post->published_at = now();
        $post->save();

        $this->announceIfPublished($post, $wasPublished);

        return $post;
    }

    /** Take a published post back to draft. */
    public function unpublish(Post $post): Post
    {
        $post->status = Post::STATUS_DRAFT;
        $post->save();

        return $post;
    }

    /** Schedule for a future moment. A past date publishes at once instead. */
    public function schedule(Post $post, $when): Post
    {
        $when = $when instanceof \DateTimeInterface ? Carbon::parse($when) : Carbon::parse($when);

        if ($when->lte(now())) {
            return $this->publish($post);
        }

        $post->status = Post::STATUS_SCHEDULED;
        $post->published_at = $when;
        $post->save();

        return $post;
    }

    /** Cancel a schedule, returning the post to draft. */
    public function cancelSchedule(Post $post): Post
    {
        $post->status = Post::STATUS_DRAFT;
        $post->save();

        return $post;
    }

    /**
     * Flip scheduled posts whose moment has arrived over to published.
     * Called by the scheduler command; returns how many were released.
     */
    public function releaseDueScheduled(): int
    {
        return Post::query()
            ->where('status', Post::STATUS_SCHEDULED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['status' => Post::STATUS_PUBLISHED]);
    }

    /**
     * Work out the stored (status, published_at) pair from what the form sent.
     * Keeps the two columns consistent — a scheduled post always has a future
     * date, a published one always has a date.
     */
    private function resolvePublishing(?string $status, $publishedAt): array
    {
        $date = $publishedAt ? Carbon::parse($publishedAt) : null;

        $status = in_array($status, [Post::STATUS_DRAFT, Post::STATUS_PUBLISHED, Post::STATUS_SCHEDULED], true)
            ? $status
            : Post::STATUS_PUBLISHED;

        return match ($status) {
            Post::STATUS_DRAFT => [Post::STATUS_DRAFT, $date],

            Post::STATUS_SCHEDULED => $date && $date->gt(now())
                ? [Post::STATUS_SCHEDULED, $date]
                // A "schedule" with no future date is really an immediate publish.
                : [Post::STATUS_PUBLISHED, $date ?? now()],

            default => $date && $date->gt(now())
                // A publish dated in the future is a schedule.
                ? [Post::STATUS_SCHEDULED, $date]
                : [Post::STATUS_PUBLISHED, $date ?? now()],
        };
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
            ->map(fn ($value) => $this->resolveTagId((string) $value, $categories))
            ->filter()
            ->unique()
            ->values();

        $post->tags()->sync($tagIds);
    }

    /**
     * Resolve one submitted category value to a tag id, or null to ignore it.
     *
     * Categories carry SoftDeletes and `tags.slug` / `tags.name` are unique, so
     * the lookup MUST see removed rows: creating a fresh row for a category a
     * moderator had removed hit the unique index and threw a 500. A removed
     * category is simply not attached — it stays removed, exactly as
     * Tag::options() already reports to the post form.
     */
    private function resolveTagId(string $value, array $categories): ?int
    {
        // A canonical shipped category, given either by slug or by Arabic name.
        $slug = isset($categories[$value]) ? $value : (array_search($value, $categories, true) ?: null);

        if ($slug !== null) {
            $existing = Tag::withTrashed()->where('slug', $slug)->first();

            if ($existing) {
                return $existing->trashed() ? null : $existing->id;
            }

            return Tag::create(['slug' => $slug, 'name' => $categories[$slug]])->id;
        }

        // Any other tag that already exists, matched by slug or name. Removed
        // ones are excluded by the model's soft-delete scope.
        return Tag::query()
            ->where(fn ($q) => $q->where('slug', $value)->orWhere('name', $value))
            ->value('id');
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);

        // Arabic-only titles slug to an empty string; fall back to a stable token.
        if ($slug === '') {
            $slug = 'post-'.Str::lower(Str::random(8));
        }

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
