<?php

use App\Models\Post;
use App\Models\User;

it('renders the home page', function () {
    Post::factory(3)->create();

    $this->get('/')->assertOk()->assertSee('منصة المعرفة السعودية');
});

it('renders the posts index page', function () {
    Post::factory(3)->create();

    $this->get('/posts')->assertOk();
});

it('renders a single post page with its content', function () {
    $post = Post::factory()->create(['title' => 'مقال عن رؤية 2030']);

    $this->get("/posts/{$post->slug}")->assertOk()->assertSee('مقال عن رؤية 2030');
});

it('redirects guests away from the dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('redirects guests away from post creation', function () {
    $this->get('/posts/create')->assertRedirect('/login');
});

it('lets an authenticated user view their dashboard', function () {
    $user = User::factory()->create();
    Post::factory(2)->create(['user_id' => $user->id]);

    $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('لوحة التحكم');
});

it('lets a user create a post through the web form', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/posts', [
        'title' => 'مقال جديد عبر النموذج',
        'content' => 'محتوى تجريبي للمقال.',
        'tags_input' => 'التقنية, رؤية 2030',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('posts', ['title' => 'مقال جديد عبر النموذج', 'user_id' => $user->id]);
});

it('registers a user through the web form and logs them in', function () {
    $response = $this->post('/register', [
        'name' => 'مستخدم جديد',
        'email' => 'newweb@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'newweb@example.com']);
});
