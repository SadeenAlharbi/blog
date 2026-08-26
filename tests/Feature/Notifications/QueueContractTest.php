<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/*
 * The project runs QUEUE_CONNECTION=database. That is fine for mail, but it
 * silently breaks anything the user is waiting for in the same request if no
 * worker is running. These pin down which parts must never depend on a worker.
 */

it('sends the email verification link without a queue worker', function () {
    Notification::fake();

    // Nothing queued: the framework's VerifyEmail is a plain notification, and
    // its listener is not queued either — so registration cannot be left
    // half-finished on an installation with no worker running.
    expect(is_subclass_of(VerifyEmail::class, ShouldQueue::class))->toBeFalse();

    config(['queue.default' => 'database']);
    DB::table('jobs')->delete();

    $this->post(route('register'), [
        'name' => 'سارة',
        'email' => 'sara@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('verification.notice'));

    Notification::assertSentTo(User::where('email', 'sara@example.com')->firstOrFail(), VerifyEmail::class);
});

it('pins the in-site bell to the sync connection on every notification that queues', function () {
    // A queued notification whose `database` channel is not pinned would leave
    // the bell empty until a worker ran — which is exactly the bug this guards.
    foreach (['App\Notifications\NewCommentNotification', 'App\Notifications\AdminActionNotification'] as $class) {
        expect(is_subclass_of($class, ShouldQueue::class))->toBeTrue("{$class} should queue");

        $connections = (new $class(...containerArgsFor($class)))->viaConnections();

        expect($connections['database'] ?? null)->toBe('sync', "{$class} must deliver the bell synchronously");
    }
});

it('leaves no unresolved job behind when a bell is written', function () {
    config(['queue.default' => 'database']);
    DB::table('jobs')->delete();

    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);

    $this->actingAs(User::factory()->create())
        ->post(route('comments.store', $post), ['content' => 'تعليق']);

    // The bell landed regardless of the queue…
    expect($author->fresh()->notifications)->toHaveCount(1);

    // …and nothing failed on the way.
    expect(DB::table('failed_jobs')->count())->toBe(0);
});

it('keeps the test suite on a synchronous queue so nothing is silently deferred', function () {
    expect(config('queue.default'))->toBe('sync')
        ->and(config('mail.default'))->toBe('array');
});

/** Minimal constructor arguments for the two queued notification classes. */
function containerArgsFor(string $class): array
{
    $post = Post::factory()->create();

    return match ($class) {
        'App\Notifications\NewCommentNotification' => [
            \App\Models\Comment::factory()->create(['post_id' => $post->id]),
        ],
        // (string $action, array $context)
        'App\Notifications\AdminActionNotification' => ['post_updated', ['title' => $post->title]],
    };
}
