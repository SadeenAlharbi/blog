<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
 * One test per notification the platform promises, asserted end-to-end through
 * the real HTTP routes — not by dispatching events by hand — so a broken
 * listener, a missing event dispatch or a swallowed channel all show up here.
 *
 * The bell reads the `notifications` table, so that is what each case checks.
 */

/** The database notifications waiting for a user, newest first. */
function bellFor(User $user)
{
    return $user->fresh()->notifications;
}

/* 1 ── someone comments on my article ─────────────────────────────────────── */

it('notifies the author when someone comments on their article', function () {
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id, 'title' => 'مقالي']);

    $this->actingAs(User::factory()->create(['name' => 'خالد']))
        ->post(route('comments.store', $post), ['content' => 'تعليق'])
        ->assertRedirect();

    $note = bellFor($author)->first();

    expect(bellFor($author))->toHaveCount(1)
        ->and($note->data['type'])->toBe('comment')
        ->and($note->data['commenter_name'])->toBe('خالد')
        ->and($note->read_at)->toBeNull();
});

it('does not notify an author who comments on their own article', function () {
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);

    $this->actingAs($author)->post(route('comments.store', $post), ['content' => 'تعليق ذاتي']);

    expect(bellFor($author))->toHaveCount(0);
});

/* 2 ── a member publishes an article ──────────────────────────────────────── */

it('notifies the moderators when a member publishes an article', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->superAdmin()->create();
    $member = User::factory()->create(['name' => 'سارة']);

    $this->actingAs($member)->post(route('posts.store'), [
        'title' => 'مقال سارة', 'content' => 'محتوى.', 'status' => Post::STATUS_PUBLISHED,
    ])->assertRedirect();

    foreach ([$admin, $otherAdmin] as $moderator) {
        $note = bellFor($moderator)->first();

        expect(bellFor($moderator))->toHaveCount(1)
            ->and($note->data['action'])->toBe('post_published')
            ->and($note->data['author_name'])->toBe('سارة');
    }
});

it('does not announce a draft to the moderators', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs(User::factory()->create())->post(route('posts.store'), [
        'title' => 'مسودة', 'content' => 'محتوى.', 'status' => Post::STATUS_DRAFT,
    ])->assertRedirect();

    expect(bellFor($admin))->toHaveCount(0);
});

it('announces a draft only once, when it is finally published', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    $this->actingAs($member)->post(route('posts.store'), [
        'title' => 'مسودة تُنشر', 'content' => 'محتوى.', 'status' => Post::STATUS_DRAFT,
    ]);

    expect(bellFor($admin))->toHaveCount(0);

    $post = Post::where('title', 'مسودة تُنشر')->firstOrFail();
    $this->actingAs($member)->post(route('posts.publish', $post))->assertRedirect();

    expect(bellFor($admin))->toHaveCount(1)
        ->and(bellFor($admin)->first()->data['action'])->toBe('post_published');
});

/* 3 ── an admin edits a member's article ──────────────────────────────────── */

it('notifies the author when an admin edits their article', function () {
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id, 'title' => 'الأصل']);
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)->put(route('admin.posts.update', $post), [
        'title' => 'بعد التعديل', 'content' => 'محتوى معدّل.', 'status' => Post::STATUS_PUBLISHED,
    ])->assertRedirect();

    expect(bellFor($author)->pluck('data.action'))->toContain('post_updated');
});

/* 4 ── an admin deletes a member's article ────────────────────────────────── */

it('notifies the author when an admin deletes their article', function () {
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id, 'title' => 'مقال محذوف']);
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)->delete(route('admin.posts.destroy', $post))->assertRedirect();

    $note = bellFor($author)->first();

    expect($note->data['action'])->toBe('post_deleted')
        ->and($note->data['title'] ?? $note->data['post_title'] ?? '')->toContain('مقال محذوف');
});

/* 5 ── an admin acts on a member's comment ────────────────────────────────── */

it('notifies the comment owner when an admin hides, approves or deletes it', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->superAdmin()->create();

    $hidden = Comment::factory()->create(['user_id' => $owner->id]);
    $this->actingAs($admin)->post(route('admin.comments.hide', $hidden))->assertRedirect();

    $approved = Comment::factory()->create(['user_id' => $owner->id, 'status' => Comment::STATUS_HIDDEN]);
    $this->actingAs($admin)->post(route('admin.comments.approve', $approved))->assertRedirect();

    $deleted = Comment::factory()->create(['user_id' => $owner->id]);
    $this->actingAs($admin)->delete(route('admin.comments.destroy', $deleted))->assertRedirect();

    $actions = bellFor($owner)->pluck('data.action');

    expect($actions)->toContain('comment_hidden')
        ->and($actions)->toContain('comment_approved')
        ->and($actions)->toContain('comment_deleted');
});

/* 6 ── a member's role or account state changes ───────────────────────────── */

it('notifies a member when their role changes', function () {
    $member = User::factory()->create();
    $owner = User::factory()->superAdmin()->create();

    $this->actingAs($owner)->put(route('admin.users.role', $member), ['role' => User::ROLE_ADMIN])
        ->assertRedirect();

    expect(bellFor($member)->pluck('data.action'))->toContain('role_changed');
});

it('notifies a member when their account is deactivated and reactivated', function () {
    $member = User::factory()->create();
    $owner = User::factory()->superAdmin()->create();

    $this->actingAs($owner)->post(route('admin.users.toggleActive', $member))->assertRedirect();
    $this->actingAs($owner)->post(route('admin.users.toggleActive', $member))->assertRedirect();

    $actions = bellFor($member)->pluck('data.action');

    expect($actions)->toContain('account_deactivated')
        ->and($actions)->toContain('account_activated');
});

/* ── delivery mechanics: no duplicates, no dependence on a running worker ── */

it('records each notification exactly once', function () {
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);

    $this->actingAs(User::factory()->create())->post(route('comments.store', $post), ['content' => 'تعليق']);

    // A listener registered twice (auto-discovery plus a manual Event::listen)
    // is exactly how this used to produce two identical bells.
    expect(bellFor($author))->toHaveCount(1);
});

it('writes the bell straight to the table without a queue worker', function () {
    // The bell must never depend on `php artisan queue:work` being up: the
    // database channel is pinned to the sync connection for that reason.
    config(['queue.default' => 'database']);
    DB::table('jobs')->delete();

    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);

    $this->actingAs(User::factory()->create())->post(route('comments.store', $post), ['content' => 'تعليق']);

    expect(bellFor($author))->toHaveCount(1)
        ->and($author->fresh()->unreadNotifications()->count())->toBe(1);
});

it('keeps the unread badge and mark-as-read honest', function () {
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);

    $this->actingAs(User::factory()->create())->post(route('comments.store', $post), ['content' => 'تعليق']);

    expect($author->fresh()->unreadNotifications()->count())->toBe(1);

    $this->actingAs($author)->post(route('notifications.readAll'))->assertRedirect();

    expect($author->fresh()->unreadNotifications()->count())->toBe(0)
        ->and(bellFor($author))->toHaveCount(1);   // read, not deleted
});
