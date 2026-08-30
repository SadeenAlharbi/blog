<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

/*
 * "Continue with Google".
 *
 * Socialite is mocked at the facade, so these exercise this project's own
 * decisions — which account a Google identity maps to, and who is allowed in —
 * without ever calling Google.
 */

beforeEach(function () {
    config([
        'services.google.client_id' => 'test-client-id',
        'services.google.client_secret' => 'test-client-secret',
        'services.google.redirect' => 'http://localhost/auth/google/callback',
    ]);
});

/** Pretend Google handed back this identity. */
function googleReturns(?string $email, string $id = '11223344', ?string $name = 'سارة من Google', array $raw = []): void
{
    $socialUser = (new SocialiteUser())->map([
        'id' => $id,
        'name' => $name,
        'email' => $email,
        'user' => $raw,
    ]);

    $provider = Mockery::mock();
    $provider->shouldReceive('user')->andReturn($socialUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

/* ------------------------------- the button ------------------------------- */

it('offers the Google button on both the sign-in and the sign-up page', function () {
    foreach ([route('login'), route('register')] as $page) {
        $html = $this->get($page)->assertOk()->getContent();

        expect($html)->toContain(route('auth.google.redirect'))
            ->and($html)->toContain('Google');
    }
});

it('hides the button when Google is not configured', function () {
    config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

    $this->get(route('login'))->assertOk()->assertDontSee(route('auth.google.redirect'));
    $this->get(route('register'))->assertOk()->assertDontSee(route('auth.google.redirect'));
});

it('leaves the existing sign-in form exactly as it was', function () {
    $html = $this->get(route('login'))->assertOk()->getContent();

    // The password form is still the primary path on the page.
    expect($html)->toContain('name="email"')
        ->and($html)->toContain('name="password"')
        ->and($html)->toContain('name="remember"')
        ->and($html)->toContain('تسجيل الدخول');
});

/* ------------------------------ the redirect ------------------------------ */

it('sends the visitor to Google, asking for the account chooser', function () {
    $provider = Mockery::mock();

    // Without prompt=select_account Google silently reuses whichever account
    // the browser is already signed into, and a second address is unreachable.
    $provider->shouldReceive('with')
        ->once()
        ->with(['prompt' => 'select_account'])
        ->andReturnSelf();

    $provider->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get(route('auth.google.redirect'))->assertRedirect('https://accounts.google.com/o/oauth2/auth');
});

it('refuses to start when Google is not configured', function () {
    config(['services.google.client_id' => null]);

    $this->get(route('auth.google.redirect'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    $this->assertGuest();
});

/* ------------------- case 1: somebody entirely new ------------------------ */

it('creates an ordinary member the first time someone uses Google', function () {
    googleReturns('new@example.com', id: '999', name: 'سارة');

    $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

    $user = User::where('email', 'new@example.com')->firstOrFail();

    expect($user->name)->toBe('سارة')
        ->and($user->role)->toBe(User::ROLE_USER)      // never an administrator
        ->and($user->is_super_admin)->toBeFalsy()
        ->and($user->is_active)->toBeTrue()
        ->and($user->provider)->toBe(User::PROVIDER_GOOGLE)
        ->and($user->provider_id)->toBe('999');

    $this->assertAuthenticatedAs($user);
});

it('never stores a usable or guessable password for a Google member', function () {
    googleReturns('new@example.com');

    $this->get(route('auth.google.callback'));

    $user = User::where('email', 'new@example.com')->firstOrFail();

    // Something is stored (the column is NOT NULL) but none of the obvious
    // guesses open it, and it is hashed rather than kept in the clear.
    expect($user->password)->not->toBeEmpty()
        ->and(Hash::check('password', $user->password))->toBeFalse()
        ->and(Hash::check('', $user->password))->toBeFalse()
        ->and(Hash::check($user->email, $user->password))->toBeFalse()
        ->and(str_starts_with($user->password, '$2y$'))->toBeTrue();
});

it('treats the address as verified because Google already proved it', function () {
    googleReturns('new@example.com');

    $this->get(route('auth.google.callback'));

    // Otherwise a Google member would be barred from publishing by a
    // verification link that is never coming.
    expect(User::where('email', 'new@example.com')->firstOrFail()->email_verified_at)->not->toBeNull();
});

it('believes Google when it says the address is not verified', function () {
    googleReturns('new@example.com', raw: ['email_verified' => false]);

    $this->get(route('auth.google.callback'));

    expect(User::where('email', 'new@example.com')->firstOrFail()->email_verified_at)->toBeNull();
});

it('falls back to the address when Google sends no name', function () {
    googleReturns('someone@example.com', name: null);

    $this->get(route('auth.google.callback'));

    expect(User::where('email', 'someone@example.com')->firstOrFail()->name)->toBe('someone');
});

/* --------- case 2: an existing password account with the same email ------- */

it('links Google to the existing account instead of making a second one', function () {
    $existing = User::factory()->create([
        'email' => 'member@example.com',
        'name' => 'الاسم الأصلي',
    ]);
    $originalPassword = $existing->password;

    googleReturns('member@example.com', id: '555');

    $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

    $existing->refresh();

    expect(User::where('email', 'member@example.com')->count())->toBe(1)   // no duplicate
        ->and(User::count())->toBe(1)
        ->and($existing->provider_id)->toBe('555')                         // linked
        ->and($existing->name)->toBe('الاسم الأصلي')                       // untouched
        ->and($existing->password)->toBe($originalPassword);               // untouched

    $this->assertAuthenticatedAs($existing);
});

it('keeps an administrator\'s role when their account is linked to Google', function () {
    $admin = User::factory()->superAdmin()->create(['email' => 'boss@example.com']);

    googleReturns('boss@example.com');

    $this->get(route('auth.google.callback'))->assertRedirect(route('admin.dashboard'));

    $admin->refresh();

    // Linking must neither grant nor remove privileges.
    expect($admin->role)->toBe(User::ROLE_ADMIN)
        ->and($admin->is_super_admin)->toBeTruthy()
        ->and($admin->provider)->toBe(User::PROVIDER_GOOGLE);
});

it('keeps the traditional password sign-in working after linking', function () {
    $user = User::factory()->create([
        'email' => 'both@example.com',
        'password' => Hash::make('secret-pass'),
    ]);

    googleReturns('both@example.com');
    $this->get(route('auth.google.callback'));
    $this->post(route('logout'));

    // The password they chose still opens the same account.
    $this->post(route('login'), ['email' => 'both@example.com', 'password' => 'secret-pass'])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

/* -------------- case 3: an account already linked to Google --------------- */

it('signs a returning Google member into the same account', function () {
    $user = User::factory()->create(['email' => 'returning@example.com']);
    $user->forceFill(['provider' => User::PROVIDER_GOOGLE, 'provider_id' => '777'])->save();

    googleReturns('returning@example.com', id: '777');

    $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

    expect(User::count())->toBe(1);
    $this->assertAuthenticatedAs($user);
});

it('recognises a returning member by their Google id, not their address', function () {
    $user = User::factory()->create(['email' => 'old-address@example.com']);
    $user->forceFill(['provider' => User::PROVIDER_GOOGLE, 'provider_id' => '777'])->save();

    // Same Google account, address changed on Google's side.
    googleReturns('new-address@example.com', id: '777');

    $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

    expect(User::count())->toBe(1);              // no second account
    $this->assertAuthenticatedAs($user);
});

/* ----------------------- case 4: a disabled account ----------------------- */

it('refuses a disabled account arriving through Google', function () {
    $disabled = User::factory()->inactive()->create(['email' => 'blocked@example.com']);

    googleReturns('blocked@example.com');

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', EnsureAccountIsActive::MESSAGE);

    // No session was ever created — Google is not a way around the ban.
    $this->assertGuest();
    expect(User::count())->toBe(1);
});

it('does not hand a disabled member a fresh account through Google', function () {
    User::factory()->inactive()->create(['email' => 'blocked@example.com']);

    googleReturns('blocked@example.com', id: 'brand-new-google-id');

    $this->get(route('auth.google.callback'))->assertRedirect(route('login'));

    expect(User::count())->toBe(1)
        ->and(User::where('email', 'blocked@example.com')->first()->is_active)->toBeFalsy();
    $this->assertGuest();
});

it('refuses a disabled account that was already linked to Google', function () {
    $user = User::factory()->inactive()->create(['email' => 'blocked@example.com']);
    $user->forceFill(['provider' => User::PROVIDER_GOOGLE, 'provider_id' => '888'])->save();

    googleReturns('blocked@example.com', id: '888');

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', EnsureAccountIsActive::MESSAGE);

    $this->assertGuest();
});

/* --------------------- case 5: nothing usable returned -------------------- */

it('creates nothing when Google returns no email', function () {
    googleReturns(null);

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    expect(User::count())->toBe(0);
    $this->assertGuest();
});

it('creates nothing when Google returns a malformed email', function () {
    googleReturns('not-an-email');

    $this->get(route('auth.google.callback'))->assertRedirect(route('login'));

    expect(User::count())->toBe(0);
    $this->assertGuest();
});

it('handles the visitor cancelling on Google\'s screen', function () {
    $this->get(route('auth.google.callback', ['error' => 'access_denied']))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'تم إلغاء تسجيل الدخول عبر Google.');

    expect(User::count())->toBe(0);
    $this->assertGuest();
});

it('shows a plain message — never a stack trace — when the provider throws', function () {
    $provider = Mockery::mock();
    $provider->shouldReceive('user')->andThrow(new RuntimeException('Invalid state. client_secret=SHOULD-NOT-LEAK'));
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get(route('auth.google.callback'))->assertRedirect(route('login'));

    expect(session('error'))->not->toContain('SHOULD-NOT-LEAK')
        ->and(session('error'))->not->toContain('Invalid state');

    $this->assertGuest();
});

/* --------------------------- where it lands ------------------------------- */

it('uses the same destination rules as password sign-in', function () {
    // A remembered ordinary page is honoured…
    $this->get(route('posts.create'))->assertRedirect(route('login'));

    googleReturns('member@example.com');

    $this->get(route('auth.google.callback'))->assertRedirect(route('posts.create'));
});

it('does not strand a Google member on an admin page a guest had visited', function () {
    $this->get('/admin')->assertRedirect(route('login'));

    googleReturns('member@example.com');

    // …but an admin-only destination is dropped for an ordinary member.
    $this->get(route('auth.google.callback'))->assertRedirect(route('home'));
});

/* --------------------- nothing else was disturbed ------------------------- */

it('leaves password registration working', function () {
    $this->post(route('register'), [
        'name' => 'عضو عادي',
        'email' => 'classic@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'classic@example.com')->firstOrFail();

    // A password sign-up is not marked as a Google account.
    expect($user->provider)->toBeNull()
        ->and($user->provider_id)->toBeNull()
        ->and($user->usesProvider())->toBeFalse();
});

it('leaves the API and its Sanctum tokens alone', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);

    $this->getJson('/api/v1/posts')->assertOk();
});

it('does not expose the client secret anywhere on the auth pages', function () {
    config(['services.google.client_secret' => 'super-secret-value']);

    foreach ([route('login'), route('register')] as $page) {
        $this->get($page)->assertOk()->assertDontSee('super-secret-value');
    }
});

it('keeps the callback reachable without being signed in', function () {
    // It must never sit behind `auth` — nobody is signed in until it finishes.
    $routes = collect(app('router')->getRoutes())->first(
        fn ($r) => $r->getName() === 'auth.google.callback'
    );

    expect($routes->gatherMiddleware())->not->toContain('auth');
});
