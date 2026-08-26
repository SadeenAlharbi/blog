<?php

use Illuminate\Support\Facades\Route;

/*
 * The published spec has to describe the API this project actually serves, and
 * it has to describe Sanctum's bearer token as the one and only credential —
 * so Swagger UI shows a single Authorize box that takes a token.
 */

function spec(): array
{
    return json_decode(file_get_contents(public_path('openapi.json')), true, flags: JSON_THROW_ON_ERROR);
}

it('serves the documentation page and the spec', function () {
    $this->get('/api/documentation')->assertOk()->assertSee('swagger-ui', false);

    // The spec is streamed straight off disk as a file response, so it is read
    // back as raw content rather than through the JSON response helpers.
    $response = $this->get('/api/openapi.json')->assertOk();
    $body = json_decode($response->streamedContent(), true, flags: JSON_THROW_ON_ERROR);

    expect($body['openapi'])->toBe('3.0.3')
        ->and($body['servers'][0]['url'])->toBe('/api/v1');
});

/* ------------------------------ authentication ---------------------------- */

it('declares exactly one security scheme: a Sanctum bearer token', function () {
    $schemes = spec()['components']['securitySchemes'];

    expect(array_keys($schemes))->toBe(['sanctumAuth'])
        ->and($schemes['sanctumAuth']['type'])->toBe('http')
        ->and($schemes['sanctumAuth']['scheme'])->toBe('bearer');
});

it('carries no API-key scheme anywhere in the document', function () {
    $raw = file_get_contents(public_path('openapi.json'));

    expect($raw)->not->toContain('X-API-KEY')
        ->and($raw)->not->toContain('apiKeyAuth')
        ->and($raw)->not->toContain('"type": "apiKey"');
});

it('never ships a real credential inside the spec', function () {
    $raw = file_get_contents(public_path('openapi.json'));

    // A pasted token would look like Sanctum's "<id>|<40+ chars>" format.
    expect($raw)->not->toMatch('/\d+\|[A-Za-z0-9]{40,}/')
        ->and(strtolower($raw))->not->toContain('password123');
});

it('protects every write endpoint with the bearer token', function () {
    $paths = spec()['paths'];

    $mustBeProtected = [
        ['/posts', 'post'],
        ['/posts/{slug}', 'put'],
        ['/posts/{slug}', 'delete'],
        ['/posts/{slug}/comments', 'post'],
        ['/comments/{comment}', 'delete'],
        ['/tags', 'post'],
        ['/auth/me', 'get'],
        ['/auth/logout', 'post'],
    ];

    foreach ($mustBeProtected as [$path, $method]) {
        expect($paths[$path][$method]['security'] ?? null)
            ->toBe([['sanctumAuth' => []]], "{$method} {$path} should require the bearer token");
    }
});

it('leaves the public endpoints callable without a token', function () {
    $paths = spec()['paths'];

    foreach ([['/posts', 'get'], ['/posts/{slug}', 'get'], ['/tags', 'get'], ['/auth/login', 'post'], ['/auth/register', 'post']] as [$path, $method]) {
        // An empty list is OpenAPI's "no authentication required".
        expect($paths[$path][$method]['security'] ?? null)->toBe([], "{$method} {$path} should be public");
    }
});

/* ------------------- the spec matches the real routes --------------------- */

it('documents only endpoints that really exist', function () {
    $real = collect(Route::getRoutes())
        ->filter(fn ($r) => str_starts_with($r->uri(), 'api/v1/'))
        ->flatMap(fn ($r) => collect($r->methods())
            ->reject(fn ($m) => in_array($m, ['HEAD', 'OPTIONS'], true))
            ->map(fn ($m) => strtolower($m).' /'.substr($r->uri(), strlen('api/v1/'))))
        // Route::uri() drops the binding hint, so the article placeholder comes
        // back as {post}; the spec names it {slug}.
        ->map(fn ($e) => str_replace('{post}', '{slug}', $e))
        ->unique();

    $documented = collect(spec()['paths'])
        ->flatMap(fn ($ops, $path) => collect($ops)
            ->keys()
            ->reject(fn ($k) => $k === 'parameters')
            ->map(fn ($m) => $m.' '.$path));

    $ghosts = $documented->diff($real)->values();

    expect($ghosts->all())->toBe([], 'documented but not routed: '.$ghosts->implode(', '));
});

it('documents every article, comment, tag and auth endpoint the API serves', function () {
    $documented = collect(spec()['paths'])
        ->flatMap(fn ($ops, $path) => collect($ops)->keys()->map(fn ($m) => $m.' '.$path))
        ->all();

    foreach ([
        'post /auth/register', 'post /auth/login', 'post /auth/logout', 'get /auth/me',
        'get /posts', 'post /posts', 'get /posts/{slug}', 'put /posts/{slug}', 'delete /posts/{slug}',
        'get /posts/{slug}/comments', 'post /posts/{slug}/comments', 'delete /comments/{comment}',
        'get /tags', 'post /tags',
        'get /notifications', 'get /notifications/unread-count',
    ] as $endpoint) {
        expect($documented)->toContain($endpoint);
    }
});

it('documents the 403 an unverified account gets when writing', function () {
    $paths = spec()['paths'];

    foreach ([['/posts', 'post'], ['/posts/{slug}/comments', 'post']] as [$path, $method]) {
        expect($paths[$path][$method]['responses'])->toHaveKey('403')
            ->and($paths[$path][$method]['responses']['403']['description'])->toContain('verified');
    }
});
