<?php

use App\Models\Post;
use App\Models\User;

it('records a view when an article is opened', function () {
    $post = Post::factory()->create();

    $this->get(route('posts.show', $post))->assertOk();

    $this->assertDatabaseCount('post_views', 1);
    $this->assertDatabaseHas('post_views', ['post_id' => $post->id]);
});

it('does not count a refresh by the same visitor twice', function () {
    $post = Post::factory()->create();

    $this->get(route('posts.show', $post))->assertOk();
    $this->get(route('posts.show', $post))->assertOk();
    $this->get(route('posts.show', $post))->assertOk();

    // Three reads by the same visitor inside the dedupe window = one view.
    $this->assertDatabaseCount('post_views', 1);
});

it('counts a signed-in reader against their user id', function () {
    $reader = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($reader)->get(route('posts.show', $post))->assertOk();

    $this->assertDatabaseHas('post_views', [
        'post_id' => $post->id,
        'user_id' => $reader->id,
    ]);
});

it('counts two different signed-in readers separately', function () {
    $post = Post::factory()->create();

    $this->actingAs(User::factory()->create())->get(route('posts.show', $post))->assertOk();
    $this->actingAs(User::factory()->create())->get(route('posts.show', $post))->assertOk();

    $this->assertDatabaseCount('post_views', 2);
});

it('does not record views for an unpublished article', function () {
    $author = User::factory()->create();
    $post = Post::factory()->draft()->create(['user_id' => $author->id]);

    $this->actingAs($author)->get(route('posts.show', $post))->assertOk();

    $this->assertDatabaseCount('post_views', 0);
});

it('never stores a raw ip address', function () {
    $post = Post::factory()->create();

    $this->get(route('posts.show', $post))->assertOk();

    $view = \App\Models\PostView::first();

    expect($view->ip_hash)->not->toBeNull()
        ->and(strlen($view->ip_hash))->toBe(64)   // sha256 hex
        ->and($view->ip_hash)->not->toContain('127.0.0.1');
});
