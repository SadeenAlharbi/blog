<?php

use App\Models\Post;
use App\Models\Tag;

/*
 * GET /api/v1/tags — the category listing carries how many articles each holds.
 *
 * The count is what lets an API client rank categories. It was being computed
 * by the controller (withCount) and then dropped by the resource, so clients
 * received categories they could not order. This guards both halves: that the
 * field is emitted where it is loaded, and that it stays absent where it is
 * not — which is what keeps the change non-breaking.
 */

it('reports how many articles each category holds', function () {
    $heritage = Tag::factory()->create(['name' => 'الآثار والتراث', 'slug' => 'heritage']);
    $arts = Tag::factory()->create(['name' => 'الفنون السعودية', 'slug' => 'arts']);

    Post::factory()->count(2)->create()->each(fn ($post) => $post->tags()->attach($heritage));

    $counts = collect($this->getJson('/api/v1/tags')->assertOk()->json('data'))
        ->pluck('posts_count', 'slug');

    expect($counts['heritage'])->toBe(2)
        ->and($counts['arts'])->toBe(0);
});

it('omits the count where it was not loaded, so nothing existing breaks', function () {
    $post = Post::factory()->create([
        'status' => Post::STATUS_PUBLISHED,
        'published_at' => now()->subDay(),
    ]);

    $post->tags()->attach(Tag::factory()->create());

    // Inside an article the per-category total is meaningless — and absent.
    $tag = $this->getJson("/api/v1/posts/{$post->slug}")->assertOk()->json('data.tags.0');

    expect($tag)->toHaveKeys(['id', 'name', 'slug'])
        ->and($tag)->not->toHaveKey('posts_count');
});
