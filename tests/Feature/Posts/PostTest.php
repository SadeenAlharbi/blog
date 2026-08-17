<?php

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('lists published posts with pagination', function () {
    Post::factory(15)->create();

    $response = $this->getJson('/api/v1/posts');

    $response->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonStructure(['data', 'links', 'meta']);
});

it('shows a single post with author, tags and comments', function () {
    $post = Post::factory()->create();
    $tag = Tag::factory()->create();
    $post->tags()->attach($tag);

    $response = $this->getJson("/api/v1/posts/{$post->slug}");

    $response->assertOk()
        ->assertJsonPath('data.slug', $post->slug)
        ->assertJsonPath('data.author.id', $post->user_id)
        ->assertJsonCount(1, 'data.tags');
});

it('allows an authenticated user to create a post', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $tag = Tag::factory()->create(['name' => 'Vision 2030']);

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/posts', [
        'title' => 'Saudi Vision 2030 Milestones',
        'content' => 'A detailed look at the Kingdom\'s transformation.',
        'image' => UploadedFile::fake()->image('post.jpg'),
        'tags' => [$tag->name, 'New Tag'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Saudi Vision 2030 Milestones')
        ->assertJsonCount(2, 'data.tags');

    $this->assertDatabaseHas('posts', [
        'title' => 'Saudi Vision 2030 Milestones',
        'user_id' => $user->id,
    ]);

    $post = Post::firstWhere('title', 'Saudi Vision 2030 Milestones');
    Storage::disk('public')->assertExists($post->image);
});

it('rejects post creation without authentication', function () {
    $this->postJson('/api/v1/posts', [
        'title' => 'Unauthorized Post',
        'content' => 'Should not be created.',
    ])->assertStatus(401);
});

it('validates required fields when creating a post', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/posts', []);

    $response->assertStatus(422)->assertJsonValidationErrors(['title', 'content']);
});

it('rate limits post creation to 5 per minute per user', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/posts', [
                'title' => "Post {$i}",
                'content' => 'Some content.',
            ])
            ->assertCreated();
    }

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/posts', [
            'title' => 'One too many',
            'content' => 'Should be throttled.',
        ])
        ->assertStatus(429);

    $this->assertDatabaseCount('posts', 5);
});

it('rate limits the posts index and show to 60 per minute per IP, sharing one bucket', function () {
    $post = Post::factory()->create();

    for ($i = 0; $i < 30; $i++) {
        $this->getJson('/api/v1/posts')->assertOk();
    }

    for ($i = 0; $i < 30; $i++) {
        $this->getJson("/api/v1/posts/{$post->slug}")->assertOk();
    }

    $this->getJson('/api/v1/posts')->assertStatus(429);
    $this->getJson("/api/v1/posts/{$post->slug}")->assertStatus(429);
});
