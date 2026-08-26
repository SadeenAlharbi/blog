<?php

use App\Http\Middleware\VerifyApiKey;
use App\Models\User;

/*
 * The API key identifies the CLIENT APPLICATION and is independent of Sanctum,
 * which identifies the user. The layer only enforces once a key is configured,
 * so an installation that has not set API_KEY keeps working.
 */

it('lets requests through while no api key is configured', function () {
    config(['services.api.key' => null]);

    $this->getJson('/api/v1/posts')->assertOk();
});

it('rejects a request with no api key once one is configured', function () {
    config(['services.api.key' => 'test-client-key']);

    $this->getJson('/api/v1/posts')
        ->assertStatus(401)
        ->assertJsonPath('message', 'مفتاح الوصول (X-API-KEY) مفقود أو غير صالح.');
});

it('rejects a wrong api key', function () {
    config(['services.api.key' => 'test-client-key']);

    $this->withHeader(VerifyApiKey::HEADER, 'not-the-key')
        ->getJson('/api/v1/posts')
        ->assertStatus(401);
});

it('accepts the correct api key', function () {
    config(['services.api.key' => 'test-client-key']);

    $this->withHeader(VerifyApiKey::HEADER, 'test-client-key')
        ->getJson('/api/v1/posts')
        ->assertOk();
});

it('still requires a sanctum token behind a valid api key', function () {
    config(['services.api.key' => 'test-client-key']);

    // The client is recognised, but no user is authenticated.
    $this->withHeader(VerifyApiKey::HEADER, 'test-client-key')
        ->getJson('/api/v1/auth/me')
        ->assertStatus(401);
});

it('accepts api key plus sanctum token together', function () {
    config(['services.api.key' => 'test-client-key']);
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->withHeader(VerifyApiKey::HEADER, 'test-client-key')
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);
});

it('exposes the canonical auth endpoints', function () {
    $user = User::factory()->create(['password' => bcrypt('secret-pass')]);

    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret-pass'])
        ->assertOk()
        ->assertJsonStructure(['data' => ['user', 'token']]);

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/auth/logout')->assertOk();
});

it('keeps the original un-prefixed auth endpoints working', function () {
    $user = User::factory()->create(['password' => bcrypt('secret-pass')]);

    // Backwards compatibility for existing API consumers.
    $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'secret-pass'])->assertOk();
    $this->actingAs($user, 'sanctum')->getJson('/api/v1/user')->assertOk();
});

it('serves the openapi document and the docs page', function () {
    $this->get('/api/openapi.json')->assertOk();
    $this->get('/api/documentation')->assertOk()->assertSee('swagger-ui', false);
});
