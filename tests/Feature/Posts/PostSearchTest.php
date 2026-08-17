<?php

use App\Models\Post;
use App\Models\Tag;

it('searches posts by title', function () {
    Post::factory()->create(['title' => 'Saudi Vision 2030 Explained']);
    Post::factory()->create(['title' => 'Unrelated Article About Cooking']);

    $response = $this->getJson('/api/v1/posts?search=Vision');

    $response->assertOk()->assertJsonCount(1, 'data');
});

it('searches posts by content', function () {
    Post::factory()->create(['title' => 'A', 'content' => 'NEOM is a giga-project in Saudi Arabia.']);
    Post::factory()->create(['title' => 'B', 'content' => 'This article is about something else entirely.']);

    $response = $this->getJson('/api/v1/posts?search=NEOM');

    $response->assertOk()->assertJsonCount(1, 'data');
});

it('filters posts by tag', function () {
    $tech = Tag::factory()->create(['name' => 'Technology', 'slug' => 'technology']);
    $tourism = Tag::factory()->create(['name' => 'Tourism', 'slug' => 'tourism']);

    $techPost = Post::factory()->create();
    $techPost->tags()->attach($tech);

    $tourismPost = Post::factory()->create();
    $tourismPost->tags()->attach($tourism);

    $response = $this->getJson('/api/v1/posts?tag=technology');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $techPost->id);
});

it('paginates search results', function () {
    Post::factory(12)->create(['title' => 'Vision 2030 Update']);

    $response = $this->getJson('/api/v1/posts?search=Vision&page=2');

    $response->assertOk()->assertJsonPath('meta.current_page', 2);
});
