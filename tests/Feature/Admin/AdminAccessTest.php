<?php

use App\Models\User;

/**
 * The admin area must be closed at the SERVER, not by hiding links. Every
 * admin URL is probed here as a guest, as an ordinary user, and as a
 * deactivated admin.
 */

$adminUrls = [
    '/admin',
    '/admin/posts',
    '/admin/posts/create',
    '/admin/categories',
    '/admin/comments',
    '/admin/users',
    '/admin/analytics',
    '/admin/notifications',
];

it('redirects guests away from every admin url', function () use ($adminUrls) {
    foreach ($adminUrls as $url) {
        $this->get($url)->assertRedirect('/login');
    }
});

it('forbids an ordinary user from every admin url', function () use ($adminUrls) {
    $user = User::factory()->create();

    foreach ($adminUrls as $url) {
        $this->actingAs($user)->get($url)->assertForbidden();
    }
});

it('allows an admin into every admin url', function () use ($adminUrls) {
    $admin = User::factory()->admin()->create();

    foreach ($adminUrls as $url) {
        $this->actingAs($admin)->get($url)->assertOk();
    }
});

/*
 * A deactivated admin is not merely refused the page — the account is signed
 * out and told why, which is the behaviour the platform asks for. (The bare
 * 403 this test used to expect belongs to the API, covered just below.)
 */
it('signs a deactivated admin out and explains why', function () {
    $admin = User::factory()->admin()->inactive()->create();

    $this->actingAs($admin)
        ->followingRedirects()
        ->get('/admin')
        ->assertOk()
        ->assertSee(\App\Http\Middleware\EnsureAccountIsActive::MESSAGE, false);

    expect(auth()->check())->toBeFalse();
});

it('forbids a deactivated admin on the api', function () {
    $admin = User::factory()->admin()->inactive()->create();

    // Admin rank does not survive deactivation on the API either.
    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/auth/me')
        ->assertStatus(403)
        ->assertJsonPath('message', \App\Http\Middleware\EnsureAccountIsActive::MESSAGE);
});

it('returns json 403 for a non-admin on an admin route expecting json', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/admin/posts')
        ->assertStatus(403);
});

it('keeps the ordinary user dashboard reachable for non-admins', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();
});
