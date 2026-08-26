<?php

use App\Models\Post;
use App\Models\User;

it('lets an admin edit and delete a post belonging to someone else', function () {
    $admin = User::factory()->admin()->create();
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id, 'title' => 'عنوان أصلي']);

    $this->actingAs($admin)
        ->put(route('admin.posts.update', $post), [
            'title' => 'عنوان بعد التعديل',
            'content' => 'محتوى محدّث بواسطة المشرف.',
        ])
        ->assertRedirect(route('admin.posts.index'));

    expect($post->fresh()->title)->toBe('عنوان بعد التعديل');

    $this->actingAs($admin)
        ->delete(route('admin.posts.destroy', $post))
        ->assertRedirect(route('admin.posts.index'));

    $this->assertSoftDeleted('posts', ['id' => $post->id]);
});

it('does not let an ordinary user edit another user\'s post', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id, 'title' => 'ملك صاحبه']);

    $this->actingAs($other)
        ->put(route('posts.update', $post), ['title' => 'اختطاف', 'content' => 'محتوى'])
        ->assertForbidden();

    expect($post->fresh()->title)->toBe('ملك صاحبه');
});

it('lets an admin create a post as a draft', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.posts.store'), [
            'title' => 'مقال مسودة',
            'content' => 'محتوى المسودة.',
            'status' => Post::STATUS_DRAFT,
        ])
        ->assertRedirect(route('admin.posts.index'));

    $post = Post::where('title', 'مقال مسودة')->first();

    expect($post)->not->toBeNull()
        ->and($post->status)->toBe(Post::STATUS_DRAFT)
        ->and($post->isPublished())->toBeFalse();
});

it('publishes, unpublishes and schedules a post', function () {
    $admin = User::factory()->admin()->create();
    $post = Post::factory()->draft()->create();

    // Publish now
    $this->actingAs($admin)->post(route('admin.posts.publish', $post))->assertRedirect();
    expect($post->fresh()->status)->toBe(Post::STATUS_PUBLISHED)
        ->and($post->fresh()->isPublished())->toBeTrue();

    // Back to draft
    $this->actingAs($admin)->post(route('admin.posts.unpublish', $post))->assertRedirect();
    expect($post->fresh()->status)->toBe(Post::STATUS_DRAFT);

    // Schedule for the future
    $when = now()->addDays(2);
    $this->actingAs($admin)
        ->post(route('admin.posts.schedule', $post), ['published_at' => $when->toDateTimeString()])
        ->assertRedirect();

    expect($post->fresh()->status)->toBe(Post::STATUS_SCHEDULED)
        ->and($post->fresh()->isPublished())->toBeFalse();

    // Cancel the schedule
    $this->actingAs($admin)->post(route('admin.posts.cancelSchedule', $post))->assertRedirect();
    expect($post->fresh()->status)->toBe(Post::STATUS_DRAFT);
});

it('rejects scheduling in the past', function () {
    $admin = User::factory()->admin()->create();
    $post = Post::factory()->draft()->create();

    $this->actingAs($admin)
        ->post(route('admin.posts.schedule', $post), ['published_at' => now()->subDay()->toDateTimeString()])
        ->assertSessionHasErrors('published_at');
});

it('filters the admin post list by status', function () {
    $admin = User::factory()->admin()->create();
    Post::factory()->count(2)->create();
    Post::factory()->draft()->create(['title' => 'مسودة مميّزة']);

    $this->actingAs($admin)
        ->get(route('admin.posts.index', ['status' => Post::STATUS_DRAFT]))
        ->assertOk()
        ->assertSee('مسودة مميّزة');
});
