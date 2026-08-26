<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewCommentNotification;

/*
 * The five notifications the platform actually needs, end to end, plus the
 * delivery bug that made the whole system look broken.
 */

/* ------------------------- the delivery bug itself ------------------------ */

it('writes the bell record even when the queue is not being worked', function () {
    /*
     * The project runs QUEUE_CONNECTION=database. Before viaConnections(), the
     * whole notification was queued, so the bell record only appeared once
     * somebody ran `queue:work` — which nobody was doing, and that is why
     * notifications looked completely broken. Switching this test to the real
     * database queue (no worker running) proves the record now lands anyway.
     */
    config(['queue.default' => 'database']);

    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);

    $this->actingAs(User::factory()->create())
        ->post(route('comments.store', $post), ['content' => 'تعليق'])
        ->assertRedirect();

    expect($author->fresh()->notifications()->count())->toBe(1);
});

it('pins the database channel to the sync connection', function () {
    $notification = new NewCommentNotification(Comment::factory()->create());

    expect($notification->viaConnections())->toBe(['database' => 'sync']);
});

/* ------------------------------- the five -------------------------------- */

it('1) notifies the author when someone comments on their article', function () {
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);

    $this->actingAs(User::factory()->create(['name' => 'قارئ']))
        ->post(route('comments.store', $post), ['content' => 'تعليق جميل'])
        ->assertRedirect();

    $note = $author->notifications()->first();

    expect($author->notifications()->count())->toBe(1)
        ->and($note->data['commenter_name'])->toBe('قارئ')
        ->and($note->data['post_title'])->toBe($post->title);
});

it('2) notifies the moderators when a member publishes an article', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create(['name' => 'كاتب']);

    $this->actingAs($member)->post(route('posts.store'), [
        'title' => 'مقال جديد',
        'content' => 'محتوى المقال.',
        'status' => Post::STATUS_PUBLISHED,
    ])->assertRedirect();

    $note = $admin->notifications()->first();

    expect($admin->notifications()->count())->toBe(1)
        ->and($note->data['action'])->toBe('post_published')
        ->and($note->data['message'])->toContain('كاتب')
        ->and($note->data['url'])->not->toBeEmpty();
});

it('does not tell the moderators about an article a moderator wrote', function () {
    $other = User::factory()->admin()->create();
    $author = User::factory()->admin()->create();

    $this->actingAs($author)->post(route('posts.store'), [
        'title' => 'مقال إداري',
        'content' => 'محتوى.',
        'status' => Post::STATUS_PUBLISHED,
    ])->assertRedirect();

    expect($other->notifications()->count())->toBe(0);
});

it('does not announce a draft, and announces it once it goes live', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    $this->actingAs($member)->post(route('posts.store'), [
        'title' => 'مسودة',
        'content' => 'محتوى.',
        'status' => Post::STATUS_DRAFT,
    ])->assertRedirect();

    expect($admin->notifications()->count())->toBe(0);

    $post = Post::where('title', 'مسودة')->firstOrFail();
    $this->actingAs($member)->post(route('posts.publish', $post))->assertRedirect();

    expect($admin->fresh()->notifications()->count())->toBe(1);
});

it('announces a publish only once, not on every later edit', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    $this->actingAs($member)->post(route('posts.store'), [
        'title' => 'مقال',
        'content' => 'محتوى.',
        'status' => Post::STATUS_PUBLISHED,
    ])->assertRedirect();

    $post = Post::where('title', 'مقال')->firstOrFail();

    $this->actingAs($member)->put(route('posts.update', $post), [
        'title' => 'مقال معدّل',
        'content' => 'محتوى محدّث.',
        'status' => Post::STATUS_PUBLISHED,
    ])->assertRedirect();

    expect($admin->notifications()->count())->toBe(1);
});

it('3) notifies the author when an admin edits their article', function () {
    $admin = User::factory()->admin()->create();
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);

    $this->actingAs($admin)->put(route('admin.posts.update', $post), [
        'title' => 'عنوان من الإدارة',
        'content' => 'محتوى محدّث.',
    ])->assertRedirect();

    expect($author->notifications()->count())->toBe(1)
        ->and($author->notifications()->first()->data['action'])->toBe('post_updated');
});

it('4) notifies the author when an admin deletes their article', function () {
    $admin = User::factory()->admin()->create();
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);

    $this->actingAs($admin)->delete(route('admin.posts.destroy', $post))->assertRedirect();

    expect($author->notifications()->count())->toBe(1)
        ->and($author->notifications()->first()->data['action'])->toBe('post_deleted');
});

it('5) notifies the author when an admin acts on their comment', function () {
    $admin = User::factory()->admin()->create();
    $author = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $author->id]);

    $this->actingAs($admin)->post(route('admin.comments.hide', $comment))->assertRedirect();

    expect($author->notifications()->first()->data['action'])->toBe('comment_hidden');
});

/* --------------------------------- the UI -------------------------------- */

it('shows a notification in the bell with an unread badge', function () {
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id, 'title' => 'مقال المؤلف']);

    $this->actingAs(User::factory()->create())
        ->post(route('comments.store', $post), ['content' => 'تعليق']);

    $html = $this->actingAs($author)->get(route('dashboard'))->assertOk()->getContent();

    expect($html)->toContain('مقال المؤلف')            // the notification itself
        ->and($html)->toContain('غير مقروء');           // the unread marker
});

it('lists notifications on their own page', function () {
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id, 'title' => 'عنوان مميز']);

    $this->actingAs(User::factory()->create())
        ->post(route('comments.store', $post), ['content' => 'تعليق']);

    $this->actingAs($author)->get(route('notifications.index'))->assertOk()->assertSee('عنوان مميز');
});

it('marks one notification as read, and then all of them', function () {
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);

    foreach (['أول', 'ثانٍ'] as $text) {
        $this->actingAs(User::factory()->create())
            ->post(route('comments.store', $post), ['content' => $text]);
    }

    $author = $author->fresh();
    expect($author->unreadNotifications()->count())->toBe(2);

    $first = $author->notifications()->first();
    $this->actingAs($author)->post(route('notifications.read', $first->id))->assertRedirect();
    expect($author->fresh()->unreadNotifications()->count())->toBe(1);

    $this->actingAs($author)->post(route('notifications.readAll'))->assertRedirect();
    expect($author->fresh()->unreadNotifications()->count())->toBe(0);
});
