<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;

/* ------------------------------ comment delete ----------------------------- */

it('lets a comment author delete it through the api', function () {
    $user = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/comments/{$comment->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Comment deleted successfully.');

    $this->assertSoftDeleted('comments', ['id' => $comment->id]);
});

it('lets an admin delete anyone\'s comment through the api', function () {
    $admin = User::factory()->admin()->create();
    $comment = Comment::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/comments/{$comment->id}")
        ->assertOk();

    $this->assertSoftDeleted('comments', ['id' => $comment->id]);
});

it('refuses to delete someone else\'s comment through the api', function () {
    $other = User::factory()->create();
    $comment = Comment::factory()->create();

    $this->actingAs($other, 'sanctum')
        ->deleteJson("/api/v1/comments/{$comment->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('comments', ['id' => $comment->id]);
});

it('requires authentication to delete a comment through the api', function () {
    $comment = Comment::factory()->create();

    $this->deleteJson("/api/v1/comments/{$comment->id}")->assertStatus(401);
});

/* ------------------------------ notifications ------------------------------ */

it('lists notifications and their unread count through the api', function () {
    $owner = User::factory()->create();
    $commenter = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($commenter, 'sanctum')
        ->postJson("/api/v1/posts/{$post->slug}/comments", ['content' => 'تعليق عبر الـAPI'])
        ->assertCreated();

    $this->actingAs($owner, 'sanctum')
        ->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.unread_count', 1);

    $this->actingAs($owner, 'sanctum')
        ->getJson('/api/v1/notifications/unread-count')
        ->assertOk()
        ->assertJsonPath('data.unread_count', 1);
});

it('marks a notification as read through the api', function () {
    $owner = User::factory()->create();
    $commenter = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($commenter, 'sanctum')
        ->postJson("/api/v1/posts/{$post->slug}/comments", ['content' => 'تعليق'])
        ->assertCreated();

    $note = $owner->notifications()->first();

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/notifications/{$note->id}/read")
        ->assertOk()
        ->assertJsonPath('data.is_read', true);

    expect($owner->unreadNotifications()->count())->toBe(0);
});

it('marks all notifications as read through the api', function () {
    $owner = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    foreach (range(1, 2) as $i) {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson("/api/v1/posts/{$post->slug}/comments", ['content' => "تعليق {$i}"])
            ->assertCreated();
    }

    expect($owner->unreadNotifications()->count())->toBe(2);

    $this->actingAs($owner, 'sanctum')
        ->postJson('/api/v1/notifications/read-all')
        ->assertOk();

    expect($owner->unreadNotifications()->count())->toBe(0);
});

it('does not let a user read another user\'s notification', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->postJson("/api/v1/posts/{$post->slug}/comments", ['content' => 'تعليق'])
        ->assertCreated();

    $note = $owner->notifications()->first();

    $this->actingAs($stranger, 'sanctum')
        ->postJson("/api/v1/notifications/{$note->id}/read")
        ->assertNotFound();
});

it('requires authentication for notification endpoints', function () {
    $this->getJson('/api/v1/notifications')->assertStatus(401);
});

/* --------------------------- central category list ------------------------- */

it('refuses to create a free-text tag through the api', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/tags', ['name' => 'تصنيف مخترع تمامًا'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('name');

    $this->assertDatabaseMissing('tags', ['name' => 'تصنيف مخترع تمامًا']);
});

it('accepts a canonical category through the api', function () {
    $user = User::factory()->create();
    $canonicalName = Tag::categories()['economy'];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/tags', ['name' => $canonicalName])
        ->assertCreated();

    $this->assertDatabaseHas('tags', ['name' => $canonicalName]);
});
