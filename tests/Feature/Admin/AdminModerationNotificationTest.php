<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;

/*
 * Moderation must never be silent: whoever wrote the content learns what
 * happened to it. Notifications ride the project's existing Laravel
 * Notifications stack (database channel = the bell).
 */

it('notifies the author when an admin deletes their article', function () {
    $admin = User::factory()->admin()->create();
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id, 'title' => 'مقالي']);

    $this->actingAs($admin)->delete(route('admin.posts.destroy', $post))->assertRedirect();

    expect($author->notifications()->count())->toBe(1);

    $data = $author->notifications()->first()->data;
    expect($data['action'])->toBe('post_deleted')
        ->and($data['message'])->toContain('تم حذف مقالك');
});

it('notifies the author when an admin edits their article', function () {
    $admin = User::factory()->admin()->create();
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);

    $this->actingAs($admin)
        ->put(route('admin.posts.update', $post), [
            'title' => 'عنوان معدّل من الإدارة',
            'content' => 'محتوى محدّث.',
        ])
        ->assertRedirect();

    expect($author->notifications()->count())->toBe(1)
        ->and($author->notifications()->first()->data['action'])->toBe('post_updated');
});

it('does not notify an admin about their own article', function () {
    $admin = User::factory()->admin()->create();
    $post = Post::factory()->create(['user_id' => $admin->id]);

    $this->actingAs($admin)->delete(route('admin.posts.destroy', $post))->assertRedirect();

    expect($admin->notifications()->count())->toBe(0);
});

it('notifies the author when an admin deletes their comment', function () {
    $admin = User::factory()->admin()->create();
    $author = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $author->id]);

    $this->actingAs($admin)->delete(route('admin.comments.destroy', $comment))->assertRedirect();

    expect($author->notifications()->count())->toBe(1)
        ->and($author->notifications()->first()->data['action'])->toBe('comment_deleted');
});

it('notifies the author when an admin hides their comment', function () {
    $admin = User::factory()->admin()->create();
    $author = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $author->id]);

    $this->actingAs($admin)->post(route('admin.comments.hide', $comment))->assertRedirect();

    expect($author->notifications()->first()->data['action'])->toBe('comment_hidden');
});

it('notifies a member when their role changes', function () {
    $owner = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    $this->actingAs($owner)
        ->put(route('admin.users.role', $user), ['role' => User::ROLE_ADMIN])
        ->assertRedirect();

    expect($user->notifications()->count())->toBe(1)
        ->and($user->notifications()->first()->data['action'])->toBe('role_changed')
        ->and($user->notifications()->first()->data['message'])->toContain('صلاحيات حسابك');
});

it('leaves the existing new-comment notification working', function () {
    $owner = User::factory()->create();
    $commenter = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($commenter)
        ->post(route('comments.store', $post), ['content' => 'تعليق عادي'])
        ->assertRedirect();

    $note = $owner->notifications()->first();

    expect($owner->notifications()->count())->toBe(1)
        ->and($note->data)->toHaveKey('commenter_name')
        ->and($note->data['post_title'])->toBe($post->title);
});

/* --------------------------- categories management ------------------------ */

it('lets an admin add, rename and delete a category', function () {
    $admin = User::factory()->admin()->create();

    // Add
    $this->actingAs($admin)
        ->post(route('admin.categories.store'), ['name' => 'الابتكار والبحث'])
        ->assertRedirect();

    $tag = Tag::where('name', 'الابتكار والبحث')->first();
    expect($tag)->not->toBeNull()
        ->and($tag->slug)->not->toBe('');   // Arabic names still get a usable slug

    // Rename
    $this->actingAs($admin)
        ->put(route('admin.categories.update', $tag), ['name' => 'الابتكار'])
        ->assertRedirect();
    expect($tag->fresh()->name)->toBe('الابتكار');

    // Delete (unused). This is a SOFT delete now, so the category — and any
    // article links it had — can be restored from the admin dialog.
    $this->actingAs($admin)->delete(route('admin.categories.destroy', $tag))->assertRedirect();
    $this->assertSoftDeleted('tags', ['id' => $tag->id]);
    expect(Tag::find($tag->id))->toBeNull()
        ->and(Tag::options())->not->toHaveKey($tag->slug);
});

it('refuses to delete a category that articles still use', function () {
    $admin = User::factory()->admin()->create();
    $tag = Tag::create(['name' => 'قيد الاستخدام', 'slug' => 'in-use']);
    $post = Post::factory()->create();
    $post->tags()->attach($tag);

    $this->actingAs($admin)->delete(route('admin.categories.destroy', $tag))->assertRedirect();

    $this->assertDatabaseHas('tags', ['id' => $tag->id]);
});

it('does not let an ordinary user manage categories', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.categories.store'), ['name' => 'تصنيف مرفوض'])
        ->assertForbidden();

    $this->assertDatabaseMissing('tags', ['name' => 'تصنيف مرفوض']);
});
