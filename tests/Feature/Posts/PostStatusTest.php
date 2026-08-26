<?php

use App\Models\Post;
use App\Models\User;

it('hides drafts and scheduled posts from the public listing', function () {
    Post::factory()->create(['title' => 'مقال منشور']);
    Post::factory()->draft()->create(['title' => 'مقال مسودة']);
    Post::factory()->scheduled()->create(['title' => 'مقال مجدول']);

    $this->get(route('posts.index'))
        ->assertOk()
        ->assertSee('مقال منشور')
        ->assertDontSee('مقال مسودة')
        ->assertDontSee('مقال مجدول');
});

it('returns 404 when a guest opens a draft', function () {
    $post = Post::factory()->draft()->create();

    $this->get(route('posts.show', $post))->assertNotFound();
});

it('lets the author preview their own draft', function () {
    $author = User::factory()->create();
    $post = Post::factory()->draft()->create(['user_id' => $author->id]);

    $this->actingAs($author)->get(route('posts.show', $post))->assertOk();
});

it('lets an admin preview anyone\'s draft', function () {
    $admin = User::factory()->admin()->create();
    $post = Post::factory()->draft()->create();

    $this->actingAs($admin)->get(route('posts.show', $post))->assertOk();
});

it('does not let another ordinary user open someone\'s draft', function () {
    $other = User::factory()->create();
    $post = Post::factory()->draft()->create();

    $this->actingAs($other)->get(route('posts.show', $post))->assertNotFound();
});

it('excludes drafts from the public api listing', function () {
    Post::factory()->create(['title' => 'API منشور']);
    Post::factory()->draft()->create(['title' => 'API مسودة']);

    $response = $this->getJson('/api/v1/posts');

    $response->assertOk();
    $titles = collect($response->json('data'))->pluck('title');

    expect($titles)->toContain('API منشور')
        ->and($titles)->not->toContain('API مسودة');
});

it('publishes due scheduled posts through the service', function () {
    $post = Post::factory()->create([
        'status' => Post::STATUS_SCHEDULED,
        'published_at' => now()->subMinute(),
    ]);

    app(\App\Services\PostService::class)->releaseDueScheduled();

    expect($post->fresh()->status)->toBe(Post::STATUS_PUBLISHED);
});
