<?php

use App\Models\Tag;
use App\Models\User;

it('lists tags with post counts', function () {
    Tag::factory(3)->create();

    $response = $this->getJson('/api/v1/tags');

    $response->assertOk()->assertJsonCount(3, 'data');
});

it('allows an authenticated user to create a tag', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tags', [
        'name' => 'Renewable Energy',
    ]);

    $response->assertCreated()->assertJsonPath('data.name', 'Renewable Energy');
    $this->assertDatabaseHas('tags', ['name' => 'Renewable Energy', 'slug' => 'renewable-energy']);
});

it('rejects tag creation without authentication', function () {
    $this->postJson('/api/v1/tags', ['name' => 'Unauthorized Tag'])->assertStatus(401);
});

it('rejects duplicate tag names', function () {
    $user = User::factory()->create();
    Tag::factory()->create(['name' => 'Culture']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/tags', ['name' => 'Culture'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('name');
});

it('rate limits tag creation to 10 per minute per user', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tags', ['name' => "Tag {$i}"])
            ->assertCreated();
    }

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/tags', ['name' => 'One too many'])
        ->assertStatus(429);

    $this->assertDatabaseCount('tags', 10);
});

it('rate limits the tags index to 30 per minute per IP', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->getJson('/api/v1/tags')->assertOk();
    }

    $this->getJson('/api/v1/tags')->assertStatus(429);
});
