<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
 * Permanent erase of removed comments, from the same dialog that restores them.
 *
 * The two actions stay separate on purpose: `delete()` everywhere else is still
 * a soft delete, and this route is the only way a comment row leaves the
 * database. Its safety boundary is `onlyTrashed()` — a live comment must never
 * be erasable through it, whatever ids are posted.
 */

function trashed(?User $user = null, ?Post $post = null, string $content = 'تعليق محذوف'): Comment
{
    $comment = Comment::factory()->create([
        'user_id' => ($user ?? User::factory()->create())->id,
        'post_id' => ($post ?? Post::factory()->create())->id,
        'content' => $content,
    ]);

    $comment->delete();

    return $comment->fresh();
}

/* ------------------------- the dialog still restores ---------------------- */

it('shows the removed comments to an admin', function () {
    $admin = User::factory()->superAdmin()->create();
    trashed(User::factory()->create(['name' => 'خالد']), Post::factory()->create(['title' => 'مقال أ']), 'نص محذوف');

    $html = $this->actingAs($admin)->get(route('admin.comments.index'))->assertOk()->getContent();

    expect($html)->toContain('نص محذوف')
        ->and($html)->toContain('خالد')
        ->and($html)->toContain('مقال أ');
});

it('still restores, and offers erasing alongside it', function () {
    $admin = User::factory()->superAdmin()->create();
    $comment = trashed();

    $html = $this->actingAs($admin)->get(route('admin.comments.index'))->assertOk()->getContent();

    // Both actions live in the one dialog.
    expect($html)->toContain('استرداد المحدد')
        ->and($html)->toContain('حذف نهائي للمحدد')
        ->and($html)->toContain('data-force-delete');

    $this->actingAs($admin)
        ->post(route('admin.comments.restoreSelected'), ['ids' => [$comment->id]])
        ->assertRedirect();

    expect(Comment::find($comment->id))->not->toBeNull();
});

/* ------------------------------ erasing ----------------------------------- */

it('erases one selected comment for good', function () {
    $admin = User::factory()->superAdmin()->create();
    $comment = trashed();

    $this->actingAs($admin)
        ->post(route('admin.comments.forceDeleteSelected'), ['ids' => [$comment->id]])
        ->assertRedirect()
        ->assertSessionHas('success', 'تم حذف التعليق نهائياً.');

    // Gone from the table itself, not merely flagged.
    expect(Comment::withTrashed()->find($comment->id))->toBeNull()
        ->and(DB::table('comments')->where('id', $comment->id)->exists())->toBeFalse();
});

it('erases several selected comments at once', function () {
    $admin = User::factory()->superAdmin()->create();
    $ids = collect(range(1, 3))->map(fn () => trashed()->id)->all();

    $this->actingAs($admin)
        ->post(route('admin.comments.forceDeleteSelected'), ['ids' => $ids])
        ->assertRedirect()
        ->assertSessionHas('success', 'تم حذف 3 تعليقات نهائياً.');

    expect(Comment::withTrashed()->whereIn('id', $ids)->count())->toBe(0);
});

it('erases only what was ticked and leaves the rest removed', function () {
    $admin = User::factory()->superAdmin()->create();

    $chosen = trashed(content: 'المحدد');
    $spared = trashed(content: 'غير المحدد');

    $this->actingAs($admin)
        ->post(route('admin.comments.forceDeleteSelected'), ['ids' => [$chosen->id]])
        ->assertRedirect();

    expect(Comment::withTrashed()->find($chosen->id))->toBeNull()      // erased
        ->and(Comment::withTrashed()->find($spared->id))->not->toBeNull()  // still there
        ->and(Comment::onlyTrashed()->count())->toBe(1);               // still restorable
});

it('leaves live comments completely untouched', function () {
    $admin = User::factory()->superAdmin()->create();
    $live = Comment::factory()->create(['content' => 'تعليق نشط']);
    $removed = trashed();

    $this->actingAs($admin)
        ->post(route('admin.comments.forceDeleteSelected'), ['ids' => [$removed->id]])
        ->assertRedirect();

    expect(Comment::find($live->id))->not->toBeNull()
        ->and(Comment::find($live->id)->content)->toBe('تعليق نشط');
});

it('cannot be restored after being erased', function () {
    $admin = User::factory()->superAdmin()->create();
    $comment = trashed();

    $this->actingAs($admin)->post(route('admin.comments.forceDeleteSelected'), ['ids' => [$comment->id]]);

    // Restoring the same id now finds nothing and says so.
    $this->actingAs($admin)
        ->post(route('admin.comments.restoreSelected'), ['ids' => [$comment->id]])
        ->assertRedirect()
        ->assertSessionHas('error', 'لم يتم استرداد أي تعليق.');

    expect(Comment::withTrashed()->find($comment->id))->toBeNull();
});

it('drops the erased comment out of the dialog and off the count', function () {
    $admin = User::factory()->superAdmin()->create();
    $comment = trashed(content: 'سيُمحى');

    $this->actingAs($admin)->get(route('admin.comments.index'))->assertOk()->assertSee('سيُمحى');

    $this->actingAs($admin)->post(route('admin.comments.forceDeleteSelected'), ['ids' => [$comment->id]]);

    $this->actingAs($admin)->get(route('admin.comments.index'))
        ->assertOk()
        ->assertDontSee('سيُمحى')
        // Nothing removed is left, so the dialog trigger is gone entirely.
        ->assertDontSee('data-modal-open="restore-comments"', false);
});

/* --------------------- only soft-deleted comments ------------------------- */

it('refuses to erase a comment that is still live', function () {
    $admin = User::factory()->superAdmin()->create();
    $live = Comment::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.comments.forceDeleteSelected'), ['ids' => [$live->id]])
        ->assertRedirect()
        ->assertSessionHas('error', 'لم يتم حذف أي تعليق. يمكن الحذف النهائي للتعليقات المحذوفة فقط.');

    expect(Comment::find($live->id))->not->toBeNull();
});

it('skips a live id but still erases the removed ones sent with it', function () {
    $admin = User::factory()->superAdmin()->create();
    $live = Comment::factory()->create();
    $removed = trashed();

    $this->actingAs($admin)
        ->post(route('admin.comments.forceDeleteSelected'), ['ids' => [$live->id, $removed->id]])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Comment::find($live->id))->not->toBeNull()                 // untouched
        ->and(Comment::withTrashed()->find($removed->id))->toBeNull(); // erased
});

it('ignores an id that does not exist at all', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.comments.forceDeleteSelected'), ['ids' => [999999]])
        ->assertRedirect()
        ->assertSessionHas('error');
});

/* ---------------------------- empty selection ----------------------------- */

it('erases nothing when no comment was selected', function () {
    $admin = User::factory()->superAdmin()->create();
    $comment = trashed();

    $this->actingAs($admin)
        ->post(route('admin.comments.forceDeleteSelected'), [])
        ->assertRedirect()
        ->assertSessionHas('error', 'يرجى تحديد تعليق واحد على الأقل.');

    // An empty selection must never be read as "all".
    expect(Comment::onlyTrashed()->count())->toBe(1)
        ->and(Comment::withTrashed()->find($comment->id))->not->toBeNull();
});

it('erases nothing when the selection is an empty array', function () {
    $admin = User::factory()->superAdmin()->create();
    trashed();

    $this->actingAs($admin)
        ->post(route('admin.comments.forceDeleteSelected'), ['ids' => []])
        ->assertRedirect()
        ->assertSessionHas('error', 'يرجى تحديد تعليق واحد على الأقل.');

    expect(Comment::onlyTrashed()->count())->toBe(1);
});

/* ------------------------------ who may do it ----------------------------- */

it('refuses erasing to an ordinary member', function () {
    $comment = trashed();

    $this->actingAs(User::factory()->create())
        ->post(route('admin.comments.forceDeleteSelected'), ['ids' => [$comment->id]])
        ->assertForbidden();

    expect(Comment::withTrashed()->find($comment->id))->not->toBeNull();
});

it('refuses erasing to the comment\'s own author', function () {
    $author = User::factory()->create();
    $comment = trashed($author);

    // Owning the comment is not the same as being allowed to erase it.
    $this->actingAs($author)
        ->post(route('admin.comments.forceDeleteSelected'), ['ids' => [$comment->id]])
        ->assertForbidden();

    expect(Comment::withTrashed()->find($comment->id))->not->toBeNull();
});

it('refuses erasing to a guest', function () {
    $comment = trashed();

    $this->post(route('admin.comments.forceDeleteSelected'), ['ids' => [$comment->id]])
        ->assertRedirect(route('login'));

    expect(Comment::withTrashed()->find($comment->id))->not->toBeNull();
});

it('allows a promoted admin, not just the owner account', function () {
    $comment = trashed();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.comments.forceDeleteSelected'), ['ids' => [$comment->id]])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Comment::withTrashed()->find($comment->id))->toBeNull();
});

/* ------------------------------- the filters ------------------------------ */

it('keeps both filters populated for the erase flow', function () {
    $admin = User::factory()->superAdmin()->create();

    trashed(User::factory()->create(['name' => 'سارة']), Post::factory()->create(['title' => 'مقال سارة']));
    trashed(User::factory()->create(['name' => 'نورة']), Post::factory()->create(['title' => 'مقال نورة']));

    $html = $this->actingAs($admin)->get(route('admin.comments.index'))->assertOk()->getContent();

    expect($html)->toContain('جميع المستخدمين')->and($html)->toContain('جميع المقالات')
        ->and($html)->toContain('سارة')->and($html)->toContain('نورة')
        ->and($html)->toContain('مقال سارة')->and($html)->toContain('مقال نورة');
});

it('erases by the ids sent, not by whatever the filter was showing', function () {
    $admin = User::factory()->superAdmin()->create();
    $sara = User::factory()->create(['name' => 'سارة']);

    $one = trashed($sara, content: 'تعليق سارة الأول');
    $two = trashed($sara, content: 'تعليق سارة الثاني');
    $other = trashed(User::factory()->create(['name' => 'نورة']), content: 'تعليق نورة');

    // Sara has two removed comments; only one of them is ticked.
    $this->actingAs($admin)
        ->post(route('admin.comments.forceDeleteSelected'), ['ids' => [$one->id]])
        ->assertRedirect();

    expect(Comment::withTrashed()->find($one->id))->toBeNull()
        ->and(Comment::withTrashed()->find($two->id))->not->toBeNull()
        ->and(Comment::withTrashed()->find($other->id))->not->toBeNull();
});

/* ------------------------------ notifications ----------------------------- */

it('leaves the notification about an erased comment working', function () {
    $admin = User::factory()->superAdmin()->create();
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);

    $this->actingAs(User::factory()->create(['name' => 'خالد']))
        ->post(route('comments.store', $post), ['content' => 'تعليق سيُمحى'])
        ->assertRedirect();

    $comment = Comment::firstOrFail();
    $note = $author->notifications()->firstOrFail();

    $comment->delete();
    $this->actingAs($admin)->post(route('admin.comments.forceDeleteSelected'), ['ids' => [$comment->id]]);

    // The notification row survives — it stores its own snapshot, not a link.
    expect($author->fresh()->notifications)->toHaveCount(1);

    // Opening it still lands on the article instead of erroring.
    $this->actingAs($author)
        ->post(route('notifications.read', $note->id))
        ->assertRedirect(route('posts.show', $post).'#comment-'.$comment->id);

    $this->actingAs($author)->get(route('posts.show', $post))->assertOk();

    // And the notifications page still renders it.
    $this->actingAs($author)->get(route('notifications.index'))->assertOk()->assertSee('خالد');
});

it('does not send a new notification when a comment is erased', function () {
    $admin = User::factory()->superAdmin()->create();
    $author = User::factory()->create();
    $comment = trashed($author);

    $before = $author->fresh()->notifications()->count();

    $this->actingAs($admin)->post(route('admin.comments.forceDeleteSelected'), ['ids' => [$comment->id]]);

    expect($author->fresh()->notifications()->count())->toBe($before);
});

/* ------------------------- the article is unharmed ------------------------ */

it('leaves the article and its other comments intact', function () {
    $admin = User::factory()->superAdmin()->create();
    $post = Post::factory()->create();

    $kept = Comment::factory()->create(['post_id' => $post->id, 'content' => 'تعليق باقٍ']);
    $erased = trashed(post: $post);

    $this->actingAs($admin)->post(route('admin.comments.forceDeleteSelected'), ['ids' => [$erased->id]]);

    expect(Post::find($post->id))->not->toBeNull()
        ->and(Comment::find($kept->id))->not->toBeNull();

    $this->get(route('posts.show', $post))->assertOk()->assertSee('تعليق باقٍ');
});
