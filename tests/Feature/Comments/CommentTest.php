<?php

use App\Models\Post;
use App\Models\User;
use App\Notifications\NewCommentNotification;
use Illuminate\Support\Facades\Notification;

it('allows an authenticated user to comment on a post', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $commenter = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($commenter, 'sanctum')->postJson("/api/v1/posts/{$post->slug}/comments", [
        'content' => 'Great article about Vision 2030!',
    ]);

    $response->assertCreated()->assertJsonPath('data.content', 'Great article about Vision 2030!');

    $this->assertDatabaseHas('comments', [
        'post_id' => $post->id,
        'user_id' => $commenter->id,
        'content' => 'Great article about Vision 2030!',
    ]);

    Notification::assertSentTo($owner, NewCommentNotification::class);
});

it('queues an Arabic branded email to the owner with the correct dynamic content', function () {
    Notification::fake();

    $owner = User::factory()->create(['name' => 'صاحب المقال']);
    $commenter = User::factory()->create(['name' => 'كاتب التعليق']);
    $post = Post::factory()->create(['user_id' => $owner->id, 'title' => 'رؤية السعودية 2030']);

    $this->actingAs($commenter, 'sanctum')->postJson("/api/v1/posts/{$post->slug}/comments", [
        'content' => 'مقال رائع ويستحق القراءة!',
    ])->assertCreated();

    Notification::assertSentTo($owner, NewCommentNotification::class, function ($notification) use ($owner, $post) {
        // Queued (async): the email never blocks the request.
        expect($notification)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);

        $mail = $notification->toMail($owner);
        $data = $mail->viewData;

        expect($mail->subject)->toBe('تعليق جديد على مقالك')
            ->and($mail->view)->toBe('emails.comment-added')
            ->and($data['post']->title)->toBe('رؤية السعودية 2030')              // article title
            ->and($data['commenterName'])->toBe('كاتب التعليق')                   // commenter name
            ->and($data['comment']->content)->toBe('مقال رائع ويستحق القراءة!')   // comment content
            ->and($data['url'])->toContain("/posts/{$post->slug}");               // correct URL

        return true;
    });

    // Only the post owner is notified — never the commenter.
    Notification::assertNotSentTo($commenter, NewCommentNotification::class);
});

it('does not notify the owner when they comment on their own post', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner, 'sanctum')->postJson("/api/v1/posts/{$post->slug}/comments", [
        'content' => 'My own comment.',
    ])->assertCreated();

    Notification::assertNothingSent();
});

it('rejects comments from unauthenticated users', function () {
    $post = Post::factory()->create();

    $this->postJson("/api/v1/posts/{$post->slug}/comments", [
        'content' => 'Anonymous comment',
    ])->assertStatus(401);

    $this->assertDatabaseCount('comments', 0);
});

it('validates comment content', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/posts/{$post->slug}/comments", [
        'content' => '',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('content');
});

it('lists comments for a post', function () {
    $post = Post::factory()->create();
    \App\Models\Comment::factory(3)->create(['post_id' => $post->id]);

    $response = $this->getJson("/api/v1/posts/{$post->slug}/comments");

    $response->assertOk()->assertJsonCount(3, 'data');
});

it('rate limits comment creation to 10 per minute per user', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->slug}/comments", ['content' => "Comment {$i}"])
            ->assertCreated();
    }

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/posts/{$post->slug}/comments", ['content' => 'One too many'])
        ->assertStatus(429);

    $this->assertDatabaseCount('comments', 10);
});

it('rate limits the comments index to 30 per minute per IP', function () {
    $post = Post::factory()->create();

    for ($i = 0; $i < 30; $i++) {
        $this->getJson("/api/v1/posts/{$post->slug}/comments")->assertOk();
    }

    $this->getJson("/api/v1/posts/{$post->slug}/comments")->assertStatus(429);
});
