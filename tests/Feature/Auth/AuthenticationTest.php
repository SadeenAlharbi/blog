<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('registers a new user and returns a token', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Sara Al-Otaibi',
        'email' => 'sara@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.user.email', 'sara@example.com')
        ->assertJsonStructure(['data' => ['user', 'token'], 'message']);

    $this->assertDatabaseHas('users', ['email' => 'sara@example.com']);
});

it('rejects registration with a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->postJson('/api/v1/register', [
        'name' => 'Someone',
        'email' => 'taken@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('email');
});

it('rate limits registration to 5 per minute per IP', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/register', [
            'name' => "User {$i}",
            'email' => "user{$i}@example.com",
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();
    }

    $this->postJson('/api/v1/register', [
        'name' => 'One too many',
        'email' => 'onetoomany@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(429);

    $this->assertDatabaseCount('users', 5);
});

it('logs in a user with valid credentials', function () {
    User::factory()->create([
        'email' => 'login@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'login@example.com',
        'password' => 'password123',
    ]);

    $response->assertOk()->assertJsonStructure(['data' => ['user', 'token'], 'message']);
});

it('rejects login with invalid credentials', function () {
    User::factory()->create([
        'email' => 'login2@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'login2@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('email');
});

it('rate limits login attempts to 5 per minute per email+IP', function () {
    User::factory()->create([
        'email' => 'bruteforce@example.com',
        'password' => Hash::make('password123'),
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/login', [
            'email' => 'bruteforce@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    $this->postJson('/api/v1/login', [
        'email' => 'bruteforce@example.com',
        'password' => 'password123',
    ])->assertStatus(429);
});

it('returns the authenticated user profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/user');

    $response->assertOk()->assertJsonPath('data.email', $user->email);
});

it('rejects profile access without authentication', function () {
    $this->getJson('/api/v1/user')->assertStatus(401);
});

it('logs out the authenticated user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/logout');

    $response->assertOk();
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('rate limits logout to 10 per minute per user', function () {
    $user = User::factory()->create();

    // Each logout deletes its own token, so a fresh token is needed per request.
    for ($i = 0; $i < 10; $i++) {
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/logout')
            ->assertOk();
    }

    $token = $user->createToken('api')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/logout')
        ->assertStatus(429);
});
