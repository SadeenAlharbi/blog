<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

/*
 * End-to-end audit of the REST API's authentication and authorization, driven
 * through real tokens rather than actingAs(), so token issue and revocation are
 * genuinely exercised. Sanctum bearer tokens are the ONLY credential — there is
 * no separate API-key requirement on any of these calls.
 */

/** Register through the API and return [user, plain token]. */
function apiRegister(string $email = 'api@example.com'): array
{
    $response = test()->postJson('/api/v1/auth/register', [
        'name' => 'مستخدم API',
        'email' => $email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated();

    $user = User::where('email', $email)->firstOrFail();
    // Verified here so the write endpoints behind `verified` are reachable —
    // the verification gate itself is covered in EmailVerificationTest.
    $user->forceFill(['email_verified_at' => now()])->save();

    return [$user->fresh(), $response->json('data.token')];
}

function bearer(string $token): array
{
    return ['Authorization' => 'Bearer '.$token];
}

/* ----------------------------- authentication ----------------------------- */

it('issues a working token on register', function () {
    [$user, $token] = apiRegister();

    expect($token)->toBeString()->not->toBeEmpty()
        ->and(PersonalAccessToken::findToken($token)?->tokenable_id)->toBe($user->id);

    $this->withHeaders(bearer($token))->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);
});

it('issues a working token on login', function () {
    $user = User::factory()->create(['email' => 'login@example.com']);

    $token = $this->postJson('/api/v1/auth/login', [
        'email' => 'login@example.com',
        'password' => 'password',
    ])->assertOk()->json('data.token');

    $this->withHeaders(bearer($token))->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('rejects login with the wrong password', function () {
    User::factory()->create(['email' => 'login@example.com']);

    $this->postJson('/api/v1/auth/login', ['email' => 'login@example.com', 'password' => 'wrong'])
        ->assertStatus(422);
});

it('revokes the token on logout so it cannot be replayed', function () {
    [, $token] = apiRegister();

    $this->withHeaders(bearer($token))->postJson('/api/v1/auth/logout')->assertOk();

    // The row is gone, so no later request can resolve this token.
    expect(PersonalAccessToken::findToken($token))->toBeNull()
        ->and(PersonalAccessToken::count())->toBe(0);

    /*
     * A real client's next request hits a fresh process. The test app is reused
     * and its `sanctum` guard still holds the user it resolved a moment ago, so
     * without this the assertion would pass on cached state instead of on the
     * revoked token. Forgetting the guards is what makes this a genuine
     * "fresh request carrying a dead token" check.
     */
    $this->app['auth']->forgetGuards();

    $this->withHeaders(bearer($token))->getJson('/api/v1/auth/me')->assertUnauthorized();
});

/* --------------------------- protected endpoints -------------------------- */

it('refuses every protected endpoint without a token', function () {
    $post = Post::factory()->create();

    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    $this->postJson('/api/v1/posts', ['title' => 'x', 'content' => 'y'])->assertUnauthorized();
    $this->putJson("/api/v1/posts/{$post->slug}", ['title' => 'x'])->assertUnauthorized();
    $this->deleteJson("/api/v1/posts/{$post->slug}")->assertUnauthorized();
    $this->postJson("/api/v1/posts/{$post->slug}/comments", ['content' => 'x'])->assertUnauthorized();
    $this->getJson('/api/v1/notifications')->assertUnauthorized();
    $this->postJson('/api/v1/tags', ['name' => 'x'])->assertUnauthorized();
});

it('refuses a token that was tampered with', function () {
    [, $token] = apiRegister();

    $this->withHeaders(bearer($token.'tampered'))->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('leaves the public endpoints open without a token', function () {
    $post = Post::factory()->create();

    $this->getJson('/api/v1/posts')->assertOk();
    $this->getJson("/api/v1/posts/{$post->slug}")->assertOk();
    $this->getJson("/api/v1/posts/{$post->slug}/comments")->assertOk();
    $this->getJson('/api/v1/tags')->assertOk();
});

/* ------------------------------- articles --------------------------------- */

it('walks the whole article lifecycle over a bearer token', function () {
    [$user, $token] = apiRegister();

    // Create
    $slug = $this->withHeaders(bearer($token))->postJson('/api/v1/posts', [
        'title' => 'مقال عبر الواجهة',
        'content' => 'محتوى المقال.',
    ])->assertCreated()->json('data.slug');

    // Read back
    $this->getJson("/api/v1/posts/{$slug}")->assertOk()->assertJsonPath('data.title', 'مقال عبر الواجهة');

    // Update. Renaming an article re-derives its slug, so the new one is read
    // back from the response rather than assuming the old URL still resolves.
    $newSlug = $this->withHeaders(bearer($token))
        ->putJson("/api/v1/posts/{$slug}", ['title' => 'عنوان معدّل', 'content' => 'محتوى معدّل.'])
        ->assertOk()
        ->json('data.slug');

    expect($newSlug)->not->toBe($slug);

    // The old address stops resolving once the slug moves.
    $this->getJson("/api/v1/posts/{$slug}")->assertNotFound();
    $this->getJson("/api/v1/posts/{$newSlug}")->assertOk()->assertJsonPath('data.title', 'عنوان معدّل');

    // Delete
    $this->withHeaders(bearer($token))->deleteJson("/api/v1/posts/{$newSlug}")->assertOk();

    expect(Post::where('user_id', $user->id)->count())->toBe(0);
});

it('paginates the article list', function () {
    Post::factory()->count(3)->create();

    $this->getJson('/api/v1/posts')
        ->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'per_page', 'total']]);
});

it('returns 404 for an article slug that never existed', function () {
    $this->getJson('/api/v1/posts/does-not-exist')->assertNotFound();
});

/* ----------------------------- authorization ------------------------------ */

it('refuses to let one member edit or delete another member\'s article', function () {
    [, $token] = apiRegister('mine@example.com');
    $someoneElse = Post::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->withHeaders(bearer($token))
        ->putJson("/api/v1/posts/{$someoneElse->slug}", ['title' => 'اختطاف'])
        ->assertForbidden();

    $this->withHeaders(bearer($token))
        ->deleteJson("/api/v1/posts/{$someoneElse->slug}")
        ->assertForbidden();

    expect($someoneElse->fresh()->title)->not->toBe('اختطاف');
});

it('lets an admin moderate any article through the API', function () {
    $admin = User::factory()->superAdmin()->create();
    $token = $admin->createToken('api')->plainTextToken;
    $post = Post::factory()->create();

    $this->withHeaders(bearer($token))->deleteJson("/api/v1/posts/{$post->slug}")->assertOk();

    expect(Post::find($post->id))->toBeNull();
});

/* -------------------------------- comments -------------------------------- */

it('creates and lists comments over the API', function () {
    [$user, $token] = apiRegister();
    $post = Post::factory()->create();

    $this->withHeaders(bearer($token))
        ->postJson("/api/v1/posts/{$post->slug}/comments", ['content' => 'تعليق عبر الواجهة'])
        ->assertCreated()
        ->assertJsonPath('data.content', 'تعليق عبر الواجهة');

    $this->getJson("/api/v1/posts/{$post->slug}/comments")
        ->assertOk()
        ->assertJsonPath('data.0.content', 'تعليق عبر الواجهة');
});

it('lets a member delete their own comment but not someone else\'s', function () {
    [$user, $token] = apiRegister();

    $mine = Comment::factory()->create(['user_id' => $user->id]);
    $theirs = Comment::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->withHeaders(bearer($token))->deleteJson("/api/v1/comments/{$mine->id}")->assertOk();
    $this->withHeaders(bearer($token))->deleteJson("/api/v1/comments/{$theirs->id}")->assertForbidden();

    expect(Comment::find($mine->id))->toBeNull()
        ->and(Comment::find($theirs->id))->not->toBeNull();
});

/* ------------------------------ tags/categories --------------------------- */

it('lists categories publicly and creates one with a token', function () {
    Tag::create(['name' => 'التقنية', 'slug' => 'technology']);

    $this->getJson('/api/v1/tags')->assertOk()->assertJsonPath('data.0.name', 'التقنية');

    [, $token] = apiRegister();

    // The API only materialises one of the platform's own categories — free
    // tagging is deliberately not offered, so categories stay a managed set.
    $slug = array_key_first(Tag::categories());

    $this->withHeaders(bearer($token))->postJson('/api/v1/tags', ['name' => $slug])
        ->assertCreated();

    $this->assertDatabaseHas('tags', ['slug' => $slug]);
});

it('refuses to invent a category that is not on the platform list', function () {
    [, $token] = apiRegister();

    $this->withHeaders(bearer($token))
        ->postJson('/api/v1/tags', ['name' => 'تصنيف مخترع'])
        ->assertStatus(422);

    $this->assertDatabaseMissing('tags', ['name' => 'تصنيف مخترع']);
});

/* ------------------------------ notifications ----------------------------- */

it('reads and clears notifications over the API', function () {
    $author = User::factory()->create();
    $token = $author->createToken('api')->plainTextToken;
    $post = Post::factory()->create(['user_id' => $author->id]);

    // A comment by someone else notifies the author.
    $this->actingAs(User::factory()->create())
        ->post(route('comments.store', $post), ['content' => 'تعليق'])
        ->assertRedirect();

    // actingAs leaves that commenter resolved on the guards; drop it so the
    // bearer token below is what identifies the caller, not the leftover user.
    $this->app['auth']->forgetGuards();

    $this->withHeaders(bearer($token))->getJson('/api/v1/notifications/unread-count')
        ->assertOk()->assertJsonPath('data.unread_count', 1);

    $this->withHeaders(bearer($token))->getJson('/api/v1/notifications')->assertOk();

    $this->withHeaders(bearer($token))->postJson('/api/v1/notifications/read-all')->assertOk();

    $this->withHeaders(bearer($token))->getJson('/api/v1/notifications/unread-count')
        ->assertOk()->assertJsonPath('data.unread_count', 0);
});

/* ---------------------------- disabled accounts --------------------------- */

it('stops a token belonging to a deactivated account', function () {
    [$user, $token] = apiRegister();

    $user->forceFill(['is_active' => false])->save();

    $this->withHeaders(bearer($token))->getJson('/api/v1/auth/me')->assertForbidden();
});

/* ------------------------- no separate key required ----------------------- */

it('needs nothing but the bearer token — no API key header anywhere', function () {
    [, $token] = apiRegister();
    $post = Post::factory()->create();

    // Every one of these succeeds with Authorization alone.
    $this->getJson('/api/v1/posts')->assertOk();
    $this->withHeaders(bearer($token))->getJson('/api/v1/auth/me')->assertOk();
    $this->withHeaders(bearer($token))
        ->postJson("/api/v1/posts/{$post->slug}/comments", ['content' => 'بدون مفتاح'])
        ->assertCreated();
});
