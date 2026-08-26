<?php

use App\Models\Tag;
use App\Models\User;

it('lists tags with post counts', function () {
    Tag::factory(3)->create();

    $response = $this->getJson('/api/v1/tags');

    $response->assertOk()->assertJsonCount(3, 'data');
});

/*
|--------------------------------------------------------------------------
| Central category list
|--------------------------------------------------------------------------
| Categories are a FIXED list defined in Tag::categories(). The API used to
| accept any free-text name, which let it create categories the web UI could
| never produce — one source of truth was the whole point of that list. The
| tests below assert the corrected behaviour.
*/

it('materialises a canonical category', function () {
    $user = User::factory()->create();

    $slug = 'economy';
    $name = Tag::categories()[$slug];

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tags', [
        'name' => $name,
    ]);

    $response->assertCreated()->assertJsonPath('data.name', $name);
    $this->assertDatabaseHas('tags', ['name' => $name, 'slug' => $slug]);
});

it('accepts a canonical category by its slug too', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/tags', ['name' => 'heritage'])
        ->assertCreated();

    $this->assertDatabaseHas('tags', ['slug' => 'heritage']);
});

it('rejects a free-text category outside the central list', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/tags', ['name' => 'Renewable Energy'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('name');

    $this->assertDatabaseMissing('tags', ['name' => 'Renewable Energy']);
});

it('rejects tag creation without authentication', function () {
    $this->postJson('/api/v1/tags', ['name' => 'Unauthorized Tag'])->assertStatus(401);
});

it('is idempotent for a category that already exists', function () {
    $user = User::factory()->create();
    $name = Tag::categories()['culture'];

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/tags', ['name' => $name])->assertCreated();

    // Asking again returns the existing row rather than creating a duplicate.
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/tags', ['name' => $name])->assertOk();

    $this->assertDatabaseCount('tags', 1);
});

it('rate limits tag creation to 10 per minute per user', function () {
    $user = User::factory()->create();

    // Ten DISTINCT canonical categories, so each call really creates one.
    $slugs = array_slice(array_keys(Tag::categories()), 0, 10);

    foreach ($slugs as $slug) {
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tags', ['name' => $slug])
            ->assertCreated();
    }

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/tags', ['name' => 'facts'])
        ->assertStatus(429);

    $this->assertDatabaseCount('tags', 10);
});

it('rate limits the tags index to 30 per minute per IP', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->getJson('/api/v1/tags')->assertOk();
    }

    $this->getJson('/api/v1/tags')->assertStatus(429);
});
