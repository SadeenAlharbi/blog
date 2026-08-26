<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

/*
 * Disabling an account must lock it out everywhere — the login screen, the API
 * login, and any session or token issued BEFORE it was disabled — while leaving
 * every piece of the member's content untouched.
 */

it('blocks a disabled account at the web login with a clear message', function () {
    $user = User::factory()->inactive()->create(['password' => bcrypt('secret-pass')]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret-pass',
    ]);

    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toBe(EnsureAccountIsActive::MESSAGE);
    $this->assertGuest();
});

it('still lets an active account log in', function () {
    $user = User::factory()->create(['password' => bcrypt('secret-pass')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('refuses an api token to a disabled account', function () {
    $user = User::factory()->inactive()->create(['password' => bcrypt('secret-pass')]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'secret-pass',
    ])
        ->assertStatus(403)
        ->assertJsonPath('message', EnsureAccountIsActive::MESSAGE);

    expect($user->tokens()->count())->toBe(0);
});

it('revokes tokens a disabled account already held', function () {
    $user = User::factory()->create();
    $user->createToken('api');

    expect($user->tokens()->count())->toBe(1);

    // The moderator disables the account…
    $user->is_active = false;
    $user->save();

    // …and the existing token stops working, and is revoked on use.
    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/auth/me')
        ->assertStatus(403)
        ->assertJsonPath('message', EnsureAccountIsActive::MESSAGE);
});

it('signs a disabled account out of an existing web session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();

    $user->is_active = false;
    $user->save();

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));
});

it('keeps the member\'s articles and comments when the account is disabled', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);
    $comment = Comment::factory()->create(['user_id' => $user->id]);

    $admin = User::factory()->superAdmin()->create();
    $this->actingAs($admin)->post(route('admin.users.toggleActive', $user))->assertRedirect();

    expect($user->fresh()->is_active)->toBeFalse();

    // Nothing was removed — only access was withdrawn.
    $this->assertDatabaseHas('users', ['id' => $user->id]);
    $this->assertDatabaseHas('posts', ['id' => $post->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('comments', ['id' => $comment->id, 'deleted_at' => null]);
});

it('can be reactivated again', function () {
    $owner = User::factory()->superAdmin()->create();
    $user = User::factory()->inactive()->create();

    $this->actingAs($owner)->post(route('admin.users.toggleActive', $user))->assertRedirect();

    expect($user->fresh()->is_active)->toBeTrue();
});

it('notifies the member when their account is disabled', function () {
    $owner = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    $this->actingAs($owner)->post(route('admin.users.toggleActive', $user))->assertRedirect();

    expect($user->notifications()->count())->toBe(1)
        ->and($user->notifications()->first()->data['action'])->toBe('account_deactivated');
});
