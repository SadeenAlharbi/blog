<?php

use App\Models\Post;
use App\Models\User;

/*
 * GET /api/v1/posts — who may see what.
 *
 * The listing defaults to published articles, exactly as before. `?status=`
 * widens it for an admin only, mirroring the rule show() already applies to a
 * single unpublished article.
 *
 * WHY A REAL TOKEN AND NOT actingAs($user, 'sanctum'):
 * actingAs() calls Auth::shouldUse('sanctum'), which makes sanctum the DEFAULT
 * guard for the request. That masks the very bug these tests exist to catch —
 * this route is public, so in production the default guard is the session one
 * and a Bearer token is only seen if the code asks the sanctum guard by name.
 * Sending a real Authorization header exercises the production path.
 */

/** Seeds one article per status, keyed by status. */
function articlesOfEveryStatus(): array
{
    return [
        'published' => Post::factory()->create([
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]),
        'draft' => Post::factory()->create([
            'status' => Post::STATUS_DRAFT,
            'published_at' => null,
        ]),
        'scheduled' => Post::factory()->create([
            'status' => Post::STATUS_SCHEDULED,
            'published_at' => now()->addWeek(),
        ]),
    ];
}

/** A plain-text Sanctum token for a fresh user, as a real client would hold. */
function tokenFor(User $user): string
{
    return $user->createToken('test')->plainTextToken;
}

/** The slugs returned by a listing request. */
function slugsFrom($response): \Illuminate\Support\Collection
{
    return collect($response->assertOk()->json('data'))->pluck('slug');
}

/* ------------------------------- the default ------------------------------- */

it('lists published articles only when no status is asked for', function () {
    $posts = articlesOfEveryStatus();

    $slugs = slugsFrom($this->getJson('/api/v1/posts'));

    expect($slugs)->toContain($posts['published']->slug)
        ->not->toContain($posts['draft']->slug)
        ->not->toContain($posts['scheduled']->slug);
});

/* ------------------------------ the guard rail ----------------------------- */

it('ignores ?status from a guest', function () {
    $posts = articlesOfEveryStatus();

    $slugs = slugsFrom($this->getJson('/api/v1/posts?status=draft'));

    expect($slugs)->not->toContain($posts['draft']->slug)
        ->toContain($posts['published']->slug);
});

it('ignores ?status from a non-admin holding a valid token', function () {
    $posts = articlesOfEveryStatus();
    $token = tokenFor(User::factory()->create());

    $slugs = slugsFrom($this->withToken($token)->getJson('/api/v1/posts?status=all'));

    expect($slugs)->not->toContain($posts['draft']->slug);
});

it('falls back to published when an admin sends an unknown status', function () {
    $posts = articlesOfEveryStatus();
    $token = tokenFor(User::factory()->admin()->create());

    $slugs = slugsFrom($this->withToken($token)->getJson('/api/v1/posts?status=anything'));

    expect($slugs)->not->toContain($posts['draft']->slug)
        ->toContain($posts['published']->slug);
});

/* --------------------------------- the grant ------------------------------- */

it('lets an admin list drafts with a bearer token', function () {
    $posts = articlesOfEveryStatus();
    $token = tokenFor(User::factory()->admin()->create());

    $slugs = slugsFrom($this->withToken($token)->getJson('/api/v1/posts?status=draft'));

    expect($slugs)->toContain($posts['draft']->slug)
        ->not->toContain($posts['published']->slug);
});

it('lets an admin list every status at once', function () {
    $posts = articlesOfEveryStatus();
    $token = tokenFor(User::factory()->admin()->create());

    $slugs = slugsFrom($this->withToken($token)->getJson('/api/v1/posts?status=all'));

    expect($slugs)->toContain($posts['published']->slug)
        ->toContain($posts['draft']->slug)
        ->toContain($posts['scheduled']->slug);
});

it('lets an admin browsing the site with a session list drafts too', function () {
    $posts = articlesOfEveryStatus();

    // The other half of the contract: a session-authenticated admin, no token.
    $slugs = slugsFrom(
        $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/v1/posts?status=draft')
    );

    expect($slugs)->toContain($posts['draft']->slug);
});

it('still reports the status of each article it returns', function () {
    articlesOfEveryStatus();
    $token = tokenFor(User::factory()->admin()->create());

    $this->withToken($token)
        ->getJson('/api/v1/posts?status=draft')
        ->assertOk()
        ->assertJsonPath('data.0.status', Post::STATUS_DRAFT)
        ->assertJsonPath('data.0.status_label', 'مسودة')
        ->assertJsonPath('data.0.is_published', false);
});
