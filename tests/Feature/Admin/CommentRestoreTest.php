<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

/*
 * The admin.comments.restore route existed but nothing in the UI called it.
 * These cover the dialog that now does, and the bulk action behind it:
 * only what the moderator ticked comes back, and it comes back as the SAME
 * row — same content, same author, same article, no duplicate.
 */

function trashedComment(?User $user = null, ?Post $post = null, string $content = 'تعليق محذوف'): Comment
{
    $comment = Comment::factory()->create([
        'user_id' => ($user ?? User::factory()->create())->id,
        'post_id' => ($post ?? Post::factory()->create())->id,
        'content' => $content,
    ]);

    $comment->delete();

    return $comment->fresh();
}

/* ------------------------------- the dialog ------------------------------- */

it('shows the restore button and the removed comments in the dialog', function () {
    $admin = User::factory()->superAdmin()->create();
    $author = User::factory()->create(['name' => 'خالد']);
    $post = Post::factory()->create(['title' => 'مقال التاريخ']);

    trashedComment($author, $post, 'نص التعليق المحذوف');

    $html = $this->actingAs($admin)->get(route('admin.comments.index'))->assertOk()->getContent();

    // The dialog trigger, not the words — the delete confirmation mentions the
    // dialog by name too, so the button element is the honest signal.
    expect($html)->toContain('data-modal-open="restore-comments"')
        ->and($html)->toContain('نص التعليق المحذوف')   // the comment itself
        ->and($html)->toContain('خالد')                  // its author
        ->and($html)->toContain('مقال التاريخ');         // its article
});

it('hides the restore button when nothing is removed', function () {
    $admin = User::factory()->superAdmin()->create();
    Comment::factory()->create();

    $this->actingAs($admin)->get(route('admin.comments.index'))
        ->assertOk()
        ->assertDontSee('data-modal-open="restore-comments"', false);
});

it('offers a filter entry for each author and article present', function () {
    $admin = User::factory()->superAdmin()->create();

    trashedComment(User::factory()->create(['name' => 'سارة']), Post::factory()->create(['title' => 'مقال أ']));
    trashedComment(User::factory()->create(['name' => 'نورة']), Post::factory()->create(['title' => 'مقال ب']));

    $html = $this->actingAs($admin)->get(route('admin.comments.index'))->assertOk()->getContent();

    expect($html)->toContain('جميع المستخدمين')
        ->and($html)->toContain('جميع المقالات')
        ->and($html)->toContain('سارة')->and($html)->toContain('نورة')
        ->and($html)->toContain('مقال أ')->and($html)->toContain('مقال ب');
});

/* ------------------------------ restoring -------------------------------- */

it('restores only the comments that were ticked', function () {
    $admin = User::factory()->superAdmin()->create();

    $chosen = trashedComment(content: 'المحدد');
    $untouched = trashedComment(content: 'غير المحدد');

    $this->actingAs($admin)
        ->post(route('admin.comments.restoreSelected'), ['ids' => [$chosen->id]])
        ->assertRedirect();

    expect(Comment::find($chosen->id))->not->toBeNull()          // back
        ->and(Comment::find($untouched->id))->toBeNull()         // still removed
        ->and(Comment::onlyTrashed()->count())->toBe(1);
});

it('restores several comments at once', function () {
    $admin = User::factory()->superAdmin()->create();

    $ids = collect(range(1, 3))->map(fn () => trashedComment()->id)->all();

    $this->actingAs($admin)
        ->post(route('admin.comments.restoreSelected'), ['ids' => $ids])
        ->assertRedirect();

    expect(Comment::whereIn('id', $ids)->count())->toBe(3)
        ->and(Comment::onlyTrashed()->count())->toBe(0);
});

it('refuses to restore anything when nothing was selected', function () {
    $admin = User::factory()->superAdmin()->create();
    $comment = trashedComment();

    $this->actingAs($admin)
        ->post(route('admin.comments.restoreSelected'), [])
        ->assertRedirect()
        ->assertSessionHas('error', 'يرجى تحديد تعليق واحد على الأقل.');

    // An empty selection must never be read as "all".
    expect(Comment::find($comment->id))->toBeNull()
        ->and(Comment::onlyTrashed()->count())->toBe(1);
});

it('ignores an id that is not actually a removed comment', function () {
    $admin = User::factory()->superAdmin()->create();
    $live = Comment::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.comments.restoreSelected'), ['ids' => [$live->id, 999999]])
        ->assertRedirect()
        ->assertSessionHas('error', 'لم يتم استرداد أي تعليق.');

    expect(Comment::find($live->id))->not->toBeNull();
});

/* ------------------------- what restoring preserves ----------------------- */

it('brings the comment back intact, with no duplicate', function () {
    $admin = User::factory()->superAdmin()->create();
    $author = User::factory()->create();
    $post = Post::factory()->create();

    $comment = trashedComment($author, $post, 'المحتوى الأصلي');
    $before = Comment::withTrashed()->count();

    $this->actingAs($admin)
        ->post(route('admin.comments.restoreSelected'), ['ids' => [$comment->id]])
        ->assertRedirect();

    $restored = Comment::findOrFail($comment->id);

    expect($restored->id)->toBe($comment->id)                 // same row
        ->and($restored->content)->toBe('المحتوى الأصلي')     // unchanged
        ->and($restored->user_id)->toBe($author->id)          // author kept
        ->and($restored->post_id)->toBe($post->id)            // article kept
        ->and($restored->deleted_at)->toBeNull()
        ->and(Comment::withTrashed()->count())->toBe($before); // nothing created
});

it('returns a restored comment to the ordinary list and off the removed one', function () {
    $admin = User::factory()->superAdmin()->create();
    $comment = trashedComment(content: 'عائد للقائمة');

    $this->actingAs($admin)->post(route('admin.comments.restoreSelected'), ['ids' => [$comment->id]]);

    $this->actingAs($admin)->get(route('admin.comments.index'))
        ->assertOk()
        ->assertSee('عائد للقائمة')
        ->assertDontSee('data-modal-open="restore-comments"', false);

    $this->actingAs($admin)->get(route('admin.comments.index', ['trashed' => 1]))
        ->assertOk()
        ->assertDontSee('عائد للقائمة');
});

it('shows the comment publicly again after it is restored', function () {
    $admin = User::factory()->superAdmin()->create();
    $post = Post::factory()->create();
    $comment = trashedComment(post: $post, content: 'تعليق ظاهر مجدداً');

    $this->get(route('posts.show', $post))->assertOk()->assertDontSee('تعليق ظاهر مجدداً');

    $this->actingAs($admin)->post(route('admin.comments.restoreSelected'), ['ids' => [$comment->id]]);

    $this->get(route('posts.show', $post))->assertOk()->assertSee('تعليق ظاهر مجدداً');
});

/* ------------------------------ who may do it ----------------------------- */

it('refuses the bulk restore to a non-admin', function () {
    $comment = trashedComment();

    $this->actingAs(User::factory()->create())
        ->post(route('admin.comments.restoreSelected'), ['ids' => [$comment->id]])
        ->assertForbidden();

    expect(Comment::find($comment->id))->toBeNull();
});

it('refuses the bulk restore to a guest', function () {
    $comment = trashedComment();

    $this->post(route('admin.comments.restoreSelected'), ['ids' => [$comment->id]])
        ->assertRedirect(route('login'));

    expect(Comment::find($comment->id))->toBeNull();
});

/* --------------------- the single-comment restore route -------------------- */

it('still restores one comment from the row button', function () {
    $admin = User::factory()->superAdmin()->create();
    $comment = trashedComment();

    $this->actingAs($admin)
        ->post(route('admin.comments.restore', $comment->id))
        ->assertRedirect();

    expect(Comment::find($comment->id))->not->toBeNull();
});

it('shows a restore button on each row of the removed list', function () {
    $admin = User::factory()->superAdmin()->create();
    trashedComment(content: 'في قائمة المحذوف');

    $this->actingAs($admin)->get(route('admin.comments.index', ['trashed' => 1]))
        ->assertOk()
        ->assertSee('في قائمة المحذوف')
        ->assertSee('استرداد');
});
