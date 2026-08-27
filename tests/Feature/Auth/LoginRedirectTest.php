<?php

use App\Models\Post;
use App\Models\User;

/*
 * Where signing in lands you.
 *
 * The defect these guard against: EnsureUserIsAdmin sends a guest who touched
 * an /admin URL to the login screen AND remembers /admin as their destination.
 * The next person to sign in on that browser — an ordinary member — was then
 * redirected straight into a 403 that had nothing to do with them.
 */

function signIn(string $email = 'member@example.com')
{
    return test()->post(route('login'), ['email' => $email, 'password' => 'password']);
}

/* ------------------------------ the defect -------------------------------- */

it('does not strand a member on the admin page a guest had visited', function () {
    // A guest touches an admin URL — the destination gets remembered.
    $this->get('/admin')->assertRedirect(route('login'));
    // url() builds from APP_URL, so this holds whatever host/port the install
    // runs on — hard-coding "http://localhost" only passes on some machines.
    expect(session('url.intended'))->toBe(url('/admin'));

    // An ordinary member signs in on that same browser.
    User::factory()->create(['email' => 'member@example.com']);

    signIn()->assertRedirect(route('home'));   // NOT /admin

    // And following it really is the site, not a 403.
    $this->get(route('home'))->assertOk();
});

it('clears the remembered admin page instead of leaving it for later', function () {
    $this->get('/admin');
    User::factory()->create(['email' => 'member@example.com']);

    signIn();

    // The trap is gone, not merely stepped around this once.
    expect(session('url.intended'))->toBeNull();
});

it('does not strand a member on any admin sub-page either', function () {
    foreach (['/admin/users', '/admin/comments', '/admin/analytics'] as $url) {
        $this->flushSession();
        $this->get($url)->assertRedirect(route('login'));

        $email = 'm'.md5($url).'@example.com';
        User::factory()->create(['email' => $email]);

        signIn($email)->assertRedirect(route('home'));
        $this->post(route('logout'));
    }
});

/* --------------------------- the five scenarios --------------------------- */

it('sends a guest who just signs in to the home page', function () {
    User::factory()->create(['email' => 'member@example.com']);

    signIn()->assertRedirect(route('home'));

    $this->assertAuthenticated();
    $this->get(route('home'))->assertOk();
});

it('sends an administrator to the admin dashboard', function () {
    User::factory()->superAdmin()->create(['email' => 'boss@example.com']);

    signIn('boss@example.com')->assertRedirect(route('admin.dashboard'));

    $this->get(route('admin.dashboard'))->assertOk();
});

it('sends a promoted administrator to the admin dashboard too', function () {
    User::factory()->admin()->create(['email' => 'mod@example.com']);

    signIn('mod@example.com')->assertRedirect(route('admin.dashboard'));
});

it('still refuses the admin area to a member who types the URL by hand', function () {
    User::factory()->create(['email' => 'member@example.com']);
    signIn();

    // The gate is untouched — only the redirect that led people into it.
    $this->get('/admin')->assertForbidden();
    $this->get('/admin/users')->assertForbidden();
    $this->get('/admin/comments')->assertForbidden();
});

it('returns a member to the public site on logout', function () {
    User::factory()->create(['email' => 'member@example.com']);
    signIn();

    $this->post(route('logout'))->assertRedirect(route('home'));

    $this->assertGuest();
    $this->get(route('home'))->assertOk();
    $this->get(route('posts.index'))->assertOk();
});

it('returns an administrator to the public site on logout as well', function () {
    User::factory()->superAdmin()->create(['email' => 'boss@example.com']);
    signIn('boss@example.com');

    $this->post(route('logout'))->assertRedirect(route('home'));

    $this->assertGuest();
    // …and the admin area is closed again straight away.
    $this->get('/admin')->assertRedirect(route('login'));
});

/* ------------ the useful half of "intended" still works ------------------- */

it('still returns a member to the ordinary page they were heading for', function () {
    // Nothing about this destination is admin-only, so it must be honoured.
    $this->get(route('posts.create'))->assertRedirect(route('login'));

    User::factory()->create(['email' => 'member@example.com']);

    signIn()->assertRedirect(route('posts.create'));
});

it('still returns a member to an article page they were heading for', function () {
    $post = Post::factory()->create();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    User::factory()->create(['email' => 'member@example.com']);

    signIn()->assertRedirect(route('dashboard'));
});

it('still takes an administrator to the admin page they were heading for', function () {
    $this->get('/admin/users')->assertRedirect(route('login'));

    User::factory()->superAdmin()->create(['email' => 'boss@example.com']);

    // For an admin the remembered page is genuinely theirs, so it is kept.
    signIn('boss@example.com')->assertRedirect(url('/admin/users'));
});

/* ------------------------------ edge cases -------------------------------- */

it('does not mistake a lookalike path for the admin area', function () {
    User::factory()->create(['email' => 'member@example.com']);

    // "/administration" is not "/admin" — a naive prefix check would drop it.
    session(['url.intended' => url('/administration')]);

    signIn()->assertRedirect(url('/administration'));
});

it('keeps a disabled account out regardless of any remembered page', function () {
    $this->get('/admin');
    User::factory()->inactive()->create(['email' => 'member@example.com']);

    signIn()->assertSessionHasErrors('email');

    $this->assertGuest();
});
