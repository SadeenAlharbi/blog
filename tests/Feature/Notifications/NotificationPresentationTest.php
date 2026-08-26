<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Support\NotificationPresenter;

/*
 * An article notice must never be drawn as a comment. All three surfaces (the
 * header bell, the member's page, the admin page) read the same presenter, so
 * these assertions cover every one of them.
 */

/* ------------------------------ the presenter ----------------------------- */

it('gives an article-published notice a document icon, not the comment bubble', function () {
    $view = NotificationPresenter::make([
        'type' => 'post',
        'action' => 'post_published',
        'message' => 'تم نشر مقال جديد بواسطة سارة.',
        'post_title' => 'مقال سارة',
    ]);

    expect($view['icon'])->toBe('document')
        ->and($view['icon'])->not->toBe('chat')
        ->and($view['message'])->toBe('تم نشر مقال جديد بواسطة سارة.')
        ->and($view['message'])->not->toContain('علّق')
        ->and($view['subject'])->toBe('مقال سارة');
});

it('keeps the comment bubble for a comment notice', function () {
    $view = NotificationPresenter::make([
        'type' => 'comment',
        'action' => 'comment_created',
        'commenter_name' => 'خالد',
        'post_title' => 'مقالي',
    ]);

    expect($view['icon'])->toBe('chat')
        ->and($view['message'])->toContain('علّق خالد على مقالك')
        ->and($view['subject'])->toBe('مقالي');
});

it('still reads a comment payload written before the type key existed', function () {
    // Rows already in the database have no `type`; the shape still identifies them.
    $view = NotificationPresenter::make(['commenter_name' => 'خالد', 'post_title' => 'مقالي']);

    expect($view['icon'])->toBe('chat')->and($view['message'])->toContain('علّق خالد');
});

it('picks a distinct icon for each moderation action', function () {
    $icons = fn (string $action) => NotificationPresenter::make([
        'type' => 'admin_action',
        'action' => $action,
        'message' => 'رسالة',
    ])['icon'];

    expect($icons('post_updated'))->toBe('pencil')
        ->and($icons('post_deleted'))->toBe('trash')
        ->and($icons('comment_deleted'))->toBe('chat')
        ->and($icons('comment_hidden'))->toBe('chat')
        ->and($icons('role_changed'))->toBe('users')
        ->and($icons('account_deactivated'))->toBe('users');
});

/* -------------------------------- rendered -------------------------------- */

it('shows the publish notice to the moderator without any comment wording', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create(['name' => 'سارة']);

    $this->actingAs($member)->post(route('posts.store'), [
        'title' => 'مقال سارة',
        'content' => 'محتوى المقال.',
        'status' => Post::STATUS_PUBLISHED,
    ])->assertRedirect();

    foreach ([route('notifications.index'), route('admin.notifications.index')] as $page) {
        $html = $this->actingAs($admin)->get($page)->assertOk()->getContent();

        expect($html)->toContain('تم نشر مقال جديد بواسطة سارة.')
            ->and($html)->toContain('مقال سارة')
            // No comment wording and no comment-bubble path on the page.
            ->and($html)->not->toContain('علّق')
            ->and($html)->not->toContain('تنبيهات التعليقات');
    }
});

it('carries the publish notice into the header bell the same way', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create(['name' => 'سارة']);

    $this->actingAs($member)->post(route('posts.store'), [
        'title' => 'مقال سارة',
        'content' => 'محتوى.',
        'status' => Post::STATUS_PUBLISHED,
    ])->assertRedirect();

    // The bell is rendered by the shared nav partial on every page.
    $html = $this->actingAs($admin)->get(route('dashboard'))->assertOk()->getContent();

    expect($html)->toContain('تم نشر مقال جديد بواسطة سارة.')
        ->and($html)->not->toContain('علّق');
});

it('sends the moderator to the article when the publish notice is clicked', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    $this->actingAs($member)->post(route('posts.store'), [
        'title' => 'مقال',
        'content' => 'محتوى.',
        'status' => Post::STATUS_PUBLISHED,
    ])->assertRedirect();

    $post = Post::where('title', 'مقال')->firstOrFail();
    $note = $admin->notifications()->firstOrFail();

    $this->actingAs($admin)
        ->post(route('admin.notifications.read', $note->id))
        ->assertRedirect(route('admin.posts.show', $post->id));   // NOT the comments page

    expect($admin->fresh()->unreadNotifications()->count())->toBe(0);
});

it('still shows a comment notice as a comment', function () {
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id, 'title' => 'مقال المؤلف']);

    $this->actingAs(User::factory()->create(['name' => 'خالد']))
        ->post(route('comments.store', $post), ['content' => 'تعليق'])
        ->assertRedirect();

    $html = $this->actingAs($author)->get(route('notifications.index'))->assertOk()->getContent();

    expect($html)->toContain('علّق خالد على مقالك')
        ->and($html)->toContain('مقال المؤلف')
        ->and($html)->not->toContain('تم نشر مقال جديد');
});

it('sends the author to the comment when a comment notice is clicked', function () {
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);

    $this->actingAs(User::factory()->create())
        ->post(route('comments.store', $post), ['content' => 'تعليق']);

    $note = $author->notifications()->firstOrFail();
    $comment = Comment::firstOrFail();

    $this->actingAs($author)
        ->post(route('notifications.read', $note->id))
        ->assertRedirect(route('posts.show', $post).'#comment-'.$comment->id);
});

it('titles the notifications page generally, not as a comments page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('الإشعارات')
        ->assertSee('آخر التنبيهات والتحديثات الخاصة بحسابك')
        ->assertDontSee('تنبيهات التعليقات على مقالاتك');
});

it('keeps the unread badge and mark-as-read working across both kinds', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    // One article notice…
    $this->actingAs($member)->post(route('posts.store'), [
        'title' => 'مقال', 'content' => 'محتوى.', 'status' => Post::STATUS_PUBLISHED,
    ]);

    // …and one comment notice, on an article the admin wrote.
    $adminPost = Post::factory()->create(['user_id' => $admin->id]);
    $this->actingAs($member)->post(route('comments.store', $adminPost), ['content' => 'تعليق']);

    expect($admin->fresh()->unreadNotifications()->count())->toBe(2);

    $this->actingAs($admin)->post(route('notifications.readAll'))->assertRedirect();

    expect($admin->fresh()->unreadNotifications()->count())->toBe(0);
});
