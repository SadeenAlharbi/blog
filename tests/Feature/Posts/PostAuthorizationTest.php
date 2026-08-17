<?php

use App\Models\Post;
use App\Models\User;

it('allows the owner to update their post', function () {
    $owner = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($owner, 'sanctum')->putJson("/api/v1/posts/{$post->slug}", [
        'title' => 'Updated Title',
    ]);

    $response->assertOk()->assertJsonPath('data.title', 'Updated Title');
});

it('allows the owner to delete their post', function () {
    $owner = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($owner, 'sanctum')->deleteJson("/api/v1/posts/{$post->slug}");

    $response->assertOk();
    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

it('forbids another user from updating a post they do not own', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($intruder, 'sanctum')->putJson("/api/v1/posts/{$post->slug}", [
        'title' => 'Hijacked Title',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => $post->title]);
});

it('forbids another user from deleting a post they do not own', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($intruder, 'sanctum')->deleteJson("/api/v1/posts/{$post->slug}");

    $response->assertForbidden();
    $this->assertDatabaseHas('posts', ['id' => $post->id]);
});

it('rate limits post update/delete to 10 per minute per user, sharing one bucket', function () {
    $owner = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    // Update only content (not title) so the slug — and therefore the URL — stays stable.
    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/posts/{$post->slug}", ['content' => "Updated content {$i}"])
            ->assertOk();
    }

    $this->actingAs($owner, 'sanctum')
        ->putJson("/api/v1/posts/{$post->slug}", ['content' => 'One too many'])
        ->assertStatus(429);

    // Delete shares the same "post-mutations" bucket, so it's throttled too.
    $this->actingAs($owner, 'sanctum')
        ->deleteJson("/api/v1/posts/{$post->slug}")
        ->assertStatus(429);

    $this->assertDatabaseHas('posts', ['id' => $post->id]);
});
