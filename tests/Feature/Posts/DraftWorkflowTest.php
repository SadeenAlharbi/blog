<?php

use App\Models\Post;
use App\Models\User;

/*
 * A member can park an article as a draft, find it again in their own
 * dashboard, edit it, and publish it whenever they like. Nothing about a draft
 * reaches the public site in the meantime.
 */

beforeEach(function () {
    $this->member = User::factory()->create();
});

it('offers both "save as draft" and "publish" on the create page', function () {
    $html = $this->actingAs($this->member)->get(route('posts.create'))->assertOk()->getContent();

    expect($html)->toContain('حفظ كمسودة')->and($html)->toContain('نشر المقال');
});

it('saves an article as a draft', function () {
    $this->actingAs($this->member)->post(route('posts.store'), [
        'title' => 'مقال قيد الكتابة',
        'content' => 'محتوى غير مكتمل.',
        'status' => Post::STATUS_DRAFT,
    ])->assertRedirect(route('dashboard'));

    $post = Post::where('title', 'مقال قيد الكتابة')->firstOrFail();

    expect($post->status)->toBe(Post::STATUS_DRAFT)
        ->and($post->isDraft())->toBeTrue();
});

it('keeps a draft out of every public surface', function () {
    $draft = Post::factory()->draft()->create(['user_id' => $this->member->id, 'title' => 'مسودة مخفية']);

    // Home page, article list, search and the API.
    $this->get('/')->assertOk()->assertDontSee('مسودة مخفية');
    $this->get(route('posts.index'))->assertOk()->assertDontSee('مسودة مخفية');
    $this->getJson('/api/v1/posts')->assertOk()->assertJsonMissing(['title' => 'مسودة مخفية']);

    // And a guest who guesses the URL gets a 404, not a peek.
    $this->get(route('posts.show', $draft))->assertNotFound();
});

it('keeps a draft out of the footer lists', function () {
    Post::factory()->draft()->create(['title' => 'مسودة الفوتر']);

    $this->get('/')->assertOk()->assertDontSee('مسودة الفوتر');
});

it('shows the draft to its author in the dashboard, marked as one', function () {
    Post::factory()->draft()->create(['user_id' => $this->member->id, 'title' => 'مسودتي']);

    $this->actingAs($this->member)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('مسودتي')
        ->assertSee('مسودة');
});

it('lets the author open and edit their own draft', function () {
    $draft = Post::factory()->draft()->create(['user_id' => $this->member->id]);

    $this->actingAs($this->member)->get(route('posts.show', $draft))->assertOk();
    $this->actingAs($this->member)->get(route('posts.edit', $draft))->assertOk();

    $this->actingAs($this->member)->put(route('posts.update', $draft), [
        'title' => 'عنوان محدّث',
        'content' => 'محتوى محدّث.',
        'status' => Post::STATUS_DRAFT,
    ])->assertRedirect();

    expect($draft->fresh()->title)->toBe('عنوان محدّث')
        ->and($draft->fresh()->status)->toBe(Post::STATUS_DRAFT);
});

it('lets the author publish the draft later, from the dashboard', function () {
    $draft = Post::factory()->draft()->create(['user_id' => $this->member->id]);

    $this->actingAs($this->member)->post(route('posts.publish', $draft))->assertRedirect();

    $draft = $draft->fresh();

    expect($draft->isPublished())->toBeTrue()
        ->and($draft->published_at)->not->toBeNull();

    $this->get(route('posts.index'))->assertOk()->assertSee($draft->title);
});

it('does not let someone else publish your draft', function () {
    $draft = Post::factory()->draft()->create(['user_id' => $this->member->id]);

    $this->actingAs(User::factory()->create())
        ->post(route('posts.publish', $draft))
        ->assertForbidden();

    expect($draft->fresh()->isDraft())->toBeTrue();
});

it('still supports the three states the platform already had', function () {
    $member = $this->member;

    $draft = Post::factory()->draft()->create(['user_id' => $member->id]);
    $published = Post::factory()->create(['user_id' => $member->id]);
    $scheduled = Post::factory()->scheduled()->create(['user_id' => $member->id]);

    expect($draft->statusLabel())->toBe('مسودة')
        ->and($published->statusLabel())->toBe('منشور')
        ->and($scheduled->statusLabel())->toBe('مجدول');

    $this->actingAs($member)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('مسودة')
        ->assertSee('منشور')
        ->assertSee('مجدول');
});
