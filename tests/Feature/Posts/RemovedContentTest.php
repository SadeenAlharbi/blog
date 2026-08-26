<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

/*
 * A reader must never meet a bare 404 because a moderator removed something
 * while they were reading it. Soft deletes keep the row, and the site answers
 * with an explanation instead.
 */

it('sends a reader of a removed article to the list with an explanation', function () {
    $post = Post::factory()->create();
    $slug = $post->slug;

    $post->delete(); // soft delete

    $this->get("/posts/{$slug}")
        ->assertRedirect(route('posts.index'))
        ->assertSessionHas('removed_notice', 'تم حذف هذا المقال من قبل الإدارة.');
});

it('renders that explanation as a toast on the destination page', function () {
    $post = Post::factory()->create();
    $slug = $post->slug;
    $post->delete();

    $this->followingRedirects()
        ->get("/posts/{$slug}")
        ->assertOk()
        ->assertSee('تم حذف هذا المقال من قبل الإدارة.', false);
});

it('answers the api with 410 rather than a bare 404 for a removed article', function () {
    $post = Post::factory()->create();
    $slug = $post->slug;
    $post->delete();

    $this->getJson("/api/v1/posts/{$slug}")->assertStatus(410);
});

it('still 404s for a slug that never existed', function () {
    $this->get('/posts/this-never-existed')->assertNotFound();
});

/* ----------------------------- liveness probe ----------------------------- */

it('reports an article as available while it is published', function () {
    $post = Post::factory()->create();
    Comment::factory()->count(2)->create(['post_id' => $post->id]);

    $this->getJson(route('posts.availability', $post->slug))
        ->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonCount(2, 'comments');
});

it('reports an article as unavailable once it is removed', function () {
    $post = Post::factory()->create();
    $slug = $post->slug;
    $post->delete();

    $this->getJson(route('posts.availability', $slug))
        ->assertOk()
        ->assertJsonPath('available', false)
        ->assertJsonPath('reason', 'deleted');
});

it('drops a removed comment from the liveness payload', function () {
    $post = Post::factory()->create();
    $kept = Comment::factory()->create(['post_id' => $post->id]);
    $removed = Comment::factory()->create(['post_id' => $post->id]);

    $removed->delete();

    $response = $this->getJson(route('posts.availability', $post->slug))->assertOk();

    expect($response->json('comments'))->toContain($kept->id)
        ->and($response->json('comments'))->not->toContain($removed->id);
});

it('hides a removed comment from the article page', function () {
    $post = Post::factory()->create();
    Comment::factory()->create(['post_id' => $post->id, 'content' => 'تعليق باقٍ']);
    $removed = Comment::factory()->create(['post_id' => $post->id, 'content' => 'تعليق محذوف']);
    $removed->delete();

    $this->get(route('posts.show', $post))
        ->assertOk()
        ->assertSee('تعليق باقٍ')
        ->assertDontSee('تعليق محذوف');
});

it('lets an admin restore a removed article', function () {
    $admin = User::factory()->admin()->create();
    $post = Post::factory()->create();
    $post->delete();

    $this->actingAs($admin)->post(route('admin.posts.restore', $post->id))->assertRedirect();

    expect(Post::find($post->id))->not->toBeNull();
});

/* ------------------------- moderator byline hiding ------------------------ */

it('does not show a byline for an article published by a moderator', function () {
    $admin = User::factory()->admin()->create(['name' => 'مشرف المنصة']);
    $post = Post::factory()->create(['user_id' => $admin->id]);

    $this->get(route('posts.show', $post))
        ->assertOk()
        ->assertDontSee('مشرف المنصة');
});

it('still shows the byline for an article written by a member', function () {
    $author = User::factory()->create(['name' => 'كاتب المنصة']);
    $post = Post::factory()->create(['user_id' => $author->id]);

    $this->get(route('posts.show', $post))
        ->assertOk()
        ->assertSee('كاتب المنصة');
});
