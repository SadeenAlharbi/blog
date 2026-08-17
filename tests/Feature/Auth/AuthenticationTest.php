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
