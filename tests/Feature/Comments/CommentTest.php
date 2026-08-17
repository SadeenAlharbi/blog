<?php

use App\Models\Post;
use App\Models\User;
use App\Notifications\NewCommentNotification;
use Illuminate\Support\Facades\Notification;

it('allows an authenticated user to comment on a post', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $commenter = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($commenter, 'sanctum')->postJson("/api/v1/posts/{$post->slug}/comments", [
        'content' => 'Great article about Vision 2030!',
    ]);

    $response->assertCreated()->assertJsonPath('data.content', 'Great article about Vision 2030!');

    $this->assertDatabaseHas('comments', [
        'post_id' => $post->id,
        'user_id' => $commenter->id,
        'content' => 'Great article about Vision 2030!',
    ]);

    Notification::assertSentTo($owner, NewCommentNotification::class);
});

it('does not notify the owner when they comment on their own post', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner, 'sanctum')->postJson("/api/v1/posts/{$post->slug}/comments", [
        'content' => 'My own comment.',
    ])->assertCreated();

    Notification::assertNothingSent();
});

it('rejects comments from unauthenticated users', function () {
    $post = Post::factory()->create();

    $this->postJson("/api/v1/posts/{$post->slug}/comments", [
        'content' => 'Anonymous comment',
    ])->assertStatus(401);

    $this->assertDatabaseCount('comments', 0);
});

it('validates comment content', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/posts/{$post->slug}/comments", [
        'content' => '',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('content');
});

it('lists comments for a post', function () {
    $post = Post::factory()->create();
    \App\Models\Comment::factory(3)->create(['post_id' => $post->id]);

    $response = $this->getJson("/api/v1/posts/{$post->slug}/comments");

    $response->assertOk()->assertJsonCount(3, 'data');
});

it('rate limits comment creation to 10 per minute per user', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->slug}/comments", ['content' => "Comment {$i}"])
            ->assertCreated();
    }

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/posts/{$post->slug}/comments", ['content' => 'One too many'])
        ->assertStatus(429);

    $this->assertDatabaseCount('comments', 10);
});
