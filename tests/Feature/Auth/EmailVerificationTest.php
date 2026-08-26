<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

/*
 * Laravel's own verification flow, wired to this project's routes. The gate is
 * deliberately narrow: writing public content waits for the link, everything
 * else (reading, the dashboard, notifications, logout, the whole admin area)
 * stays open.
 */

/* ------------------------------- registering ------------------------------ */

it('registers a new account unverified and mails the link', function () {
    Notification::fake();

    $this->post(route('register'), [
        'name' => 'سارة',
        'email' => 'sara@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'sara@example.com')->firstOrFail();

    expect($user->email_verified_at)->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('shows the notice page to an unverified user', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('فعّل بريدك الإلكتروني');
});

it('sends the notice page straight past for someone already verified', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('verification.notice'))
        ->assertRedirect(route('dashboard'));
});

/* -------------------------------- verifying ------------------------------- */

it('verifies the address when the signed link is opened', function () {
    Event::fake([Verified::class]);

    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

it('refuses a link whose hash does not match the account', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1('someone-else@example.com'),
    ]);

    $this->actingAs($user)->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('refuses an unsigned link', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))
        ->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('resends the link on request', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->post(route('verification.send'))->assertRedirect();

    Notification::assertSentTo($user, VerifyEmail::class);
});

/* ------------------------ what verification gates ------------------------- */

it('stops an unverified member from publishing or commenting', function () {
    $user = User::factory()->unverified()->create();
    $post = Post::factory()->create();

    $this->actingAs($user)->get(route('posts.create'))->assertRedirect(route('verification.notice'));

    $this->actingAs($user)->post(route('posts.store'), [
        'title' => 'مقال', 'content' => 'محتوى.', 'status' => Post::STATUS_PUBLISHED,
    ])->assertRedirect(route('verification.notice'));

    $this->actingAs($user)->post(route('comments.store', $post), ['content' => 'تعليق'])
        ->assertRedirect(route('verification.notice'));

    expect(Post::where('title', 'مقال')->exists())->toBeFalse();
});

it('lets a verified member publish and comment as before', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user)->get(route('posts.create'))->assertOk();

    $this->actingAs($user)->post(route('posts.store'), [
        'title' => 'مقال موثّق', 'content' => 'محتوى.', 'status' => Post::STATUS_PUBLISHED,
    ])->assertRedirect();

    $this->actingAs($user)->post(route('comments.store', $post), ['content' => 'تعليق'])->assertRedirect();

    expect(Post::where('title', 'مقال موثّق')->exists())->toBeTrue();
});

it('leaves reading, the dashboard and notifications open to an unverified member', function () {
    $user = User::factory()->unverified()->create();
    $post = Post::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $this->actingAs($user)->get(route('dashboard.comments'))->assertOk();
    $this->actingAs($user)->get(route('notifications.index'))->assertOk();
    $this->actingAs($user)->get(route('posts.index'))->assertOk();
    $this->actingAs($user)->get(route('posts.show', $post))->assertOk();
});

it('never blocks logging out or removing your own content', function () {
    $user = User::factory()->unverified()->create();
    $own = Post::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->delete(route('posts.destroy', $own))->assertRedirect();

    $this->actingAs($user)->post(route('logout'))->assertRedirect();
});

/* --------------------------- the admin area ------------------------------ */

it('never gates the admin area behind verification', function () {
    // An administrator is provisioned by the owner, not by self-registration.
    $admin = User::factory()->superAdmin()->unverified()->create();

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($admin)->get(route('admin.posts.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.comments.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
});

it('keeps login working for an unverified account', function () {
    $user = User::factory()->unverified()->create(['email' => 'x@example.com']);

    $this->post(route('login'), ['email' => 'x@example.com', 'password' => 'password'])
        ->assertRedirect();

    $this->assertAuthenticatedAs($user);
});

/* ------------------------------ the API side ------------------------------ */

it('registers through the API unverified and mails the link', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/register', [
        'name' => 'خالد',
        'email' => 'khaled@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated()->assertJsonPath('data.user.email', 'khaled@example.com');

    $user = User::where('email', 'khaled@example.com')->firstOrFail();

    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('stops an unverified token from writing through the API', function () {
    $user = User::factory()->unverified()->create();
    $post = Post::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/posts', ['title' => 'مقال', 'content' => 'محتوى'])
        ->assertForbidden();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/posts/{$post->slug}/comments", ['content' => 'تعليق'])
        ->assertForbidden();
});

it('still lets an unverified token read and delete its own content', function () {
    $user = User::factory()->unverified()->create();
    $own = Post::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user, 'sanctum')->getJson('/api/v1/posts')->assertOk();
    $this->actingAs($user, 'sanctum')->getJson('/api/v1/auth/me')->assertOk();
    $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/posts/{$own->slug}")->assertOk();
});
