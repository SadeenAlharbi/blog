<?php

use App\Models\Post;
use App\Models\User;
use App\Notifications\NewCommentNotification;
use Illuminate\Support\Facades\Notification;

// Queue is `sync` and mail is `array` in phpunit.xml, so a comment delivers the
// notification synchronously: the database row is written and the email is
// captured (never really sent) — perfect for asserting real behaviour.

it('creates a database notification for the post owner when a comment is added', function () {
    $owner = User::factory()->create(['name' => 'صاحب المقال']);
    $commenter = User::factory()->create(['name' => 'المعلّق']);
    $post = Post::factory()->create(['user_id' => $owner->id, 'title' => 'رؤية 2030']);

    $this->actingAs($commenter)
        ->post(route('comments.store', $post), ['content' => 'تعليق رائع على المقال'])
        ->assertRedirect();

    // Exactly one notification, for the owner only — never the commenter (no duplicate).
    expect($owner->notifications()->count())->toBe(1)
        ->and($owner->unreadNotifications()->count())->toBe(1)
        ->and($commenter->notifications()->count())->toBe(0);

    $data = $owner->notifications()->first()->data;
    expect($data['post_title'])->toBe('رؤية 2030')          // article title
        ->and($data['commenter_name'])->toBe('المعلّق')       // commenter name
        ->and($data['excerpt'])->toContain('تعليق رائع')      // comment excerpt
        ->and($data['url'])->toContain("/posts/{$post->slug}"); // review URL
});

it('sends both the database and mail channels in a single notification', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $commenter = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($commenter)
        ->post(route('comments.store', $post), ['content' => 'قناتان في إشعار واحد'])
        ->assertRedirect();

    Notification::assertSentTo(
        $owner,
        NewCommentNotification::class,
        fn ($notification, $channels) => in_array('database', $channels) && in_array('mail', $channels)
    );

    // Only one notification instance is sent (no duplicate from a second path).
    Notification::assertSentToTimes($owner, NewCommentNotification::class, 1);
    Notification::assertNotSentTo($commenter, NewCommentNotification::class);
});

it('marks a single notification as read and redirects to its target', function () {
    $owner = User::factory()->create();
    $commenter = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($commenter)->post(route('comments.store', $post), ['content' => 'أول تعليق']);

    $note = $owner->notifications()->first();
    expect($note->read_at)->toBeNull();

    $this->actingAs($owner)
        ->post(route('notifications.read', $note->id))
        ->assertRedirect();

    expect($owner->unreadNotifications()->count())->toBe(0)
        ->and($owner->notifications()->first()->read_at)->not->toBeNull();
});

it('marks all notifications as read', function () {
    $owner = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    foreach (range(1, 2) as $i) {
        $commenter = User::factory()->create();
        $this->actingAs($commenter)->post(route('comments.store', $post), ['content' => "تعليق {$i}"]);
    }

    expect($owner->unreadNotifications()->count())->toBe(2);

    $this->actingAs($owner)->post(route('notifications.readAll'))->assertRedirect();

    expect($owner->unreadNotifications()->count())->toBe(0);
});

it('does not let a user mark another user\'s notification as read', function () {
    $owner = User::factory()->create();
    $commenter = User::factory()->create();
    $stranger = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($commenter)->post(route('comments.store', $post), ['content' => 'تعليق']);
    $note = $owner->notifications()->first();

    $this->actingAs($stranger)
        ->post(route('notifications.read', $note->id))
        ->assertNotFound();

    expect($owner->unreadNotifications()->count())->toBe(1);
});

it('shows the notifications index page to an authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('notifications.index'))->assertOk();
});
