<?php

namespace App\Http\Controllers\Admin;

use App\Events\CommentModeratedByAdmin;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * How many removed comments the restore dialog loads at once. A moderator
     * restores individual comments; an unbounded list would make the page heavy
     * on a long-running site. The view says so when the cap is reached rather
     * than hiding the rest silently.
     */
    private const RESTORE_DIALOG_LIMIT = 200;

    public function index(Request $request)
    {
        $query = Comment::query()->with(['user', 'post']);

        // Removed comments are kept (SoftDeletes) and listed on request.
        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('post', fn ($p) => $p->where('title', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->string('status')->trim()->value()) {
            if (array_key_exists($status, Comment::statuses())) {
                $query->where('status', $status);
            }
        }

        /*
         * The removed comments feed the restore dialog. They are loaded with
         * the page (not behind a second request) so the dialog can filter by
         * author and by article in place, without a reload closing it.
         */
        $deletedTotal = Comment::onlyTrashed()->count();

        $deleted = Comment::onlyTrashed()
            ->with(['user:id,name', 'post:id,title,slug'])
            ->latest('deleted_at')
            ->limit(self::RESTORE_DIALOG_LIMIT)
            ->get();

        return view('admin.comments.index', [
            'comments' => $query->latest()->paginate(20)->withQueryString(),
            'statuses' => Comment::statuses(),
            'filters' => $request->only(['search', 'status', 'trashed']),
            'deleted' => $deleted,
            'deletedTotal' => $deletedTotal,
            'deletedLimit' => self::RESTORE_DIALOG_LIMIT,
            // Only the authors/articles actually present in the list, so neither
            // dropdown can offer a choice that matches nothing.
            'deletedAuthors' => $deleted->pluck('user')->filter()->unique('id')->sortBy('name')->values(),
            'deletedPosts' => $deleted->pluck('post')->filter()->unique('id')->sortBy('title')->values(),
            'counts' => [
                'all' => Comment::count(),
                'approved' => Comment::approved()->count(),
                'hidden' => Comment::hidden()->count(),
                'trashed' => $deletedTotal,
            ],
        ]);
    }

    /** Make a comment publicly visible again. */
    public function approve(Request $request, Comment $comment)
    {
        return $this->changeStatus(
            $request,
            $comment,
            Comment::STATUS_APPROVED,
            CommentModeratedByAdmin::ACTION_APPROVED,
            'تمت الموافقة على التعليق.'
        );
    }

    /** Hide a comment from the public site without deleting it. */
    public function hide(Request $request, Comment $comment)
    {
        return $this->changeStatus(
            $request,
            $comment,
            Comment::STATUS_HIDDEN,
            CommentModeratedByAdmin::ACTION_HIDDEN,
            'تم إخفاء التعليق.'
        );
    }

    public function destroy(Request $request, Comment $comment)
    {
        $this->authorize('delete', $comment);

        // Dispatched before the delete, while the relations still load.
        CommentModeratedByAdmin::dispatch($comment, $request->user(), CommentModeratedByAdmin::ACTION_DELETED);

        // Soft delete: the comment disappears from the site, but the reader
        // gets an explanation rather than a broken page, and it can be restored.
        $comment->delete();

        return back()->with('success', 'تم حذف التعليق.');
    }

    /**
     * Restore the removed comments the moderator ticked — and only those.
     *
     * An empty selection is refused rather than treated as "all": restoring
     * everything because nothing was chosen is exactly the accident this guard
     * exists to prevent. Restoring uses the existing SoftDeletes `deleted_at`,
     * so the row, its content and its links to the author and the article are
     * the same ones — nothing is re-created and no duplicate can appear.
     */
    public function restoreSelected(Request $request)
    {
        $this->authorize('moderate', new Comment());

        $validated = $request->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
        ]);

        $ids = array_unique($validated['ids'] ?? []);

        if ($ids === []) {
            return back()->with('error', 'يرجى تحديد تعليق واحد على الأقل.');
        }

        // whereIn on the trashed set: an id that is not actually removed (or
        // does not exist) is ignored instead of trusted.
        $restored = Comment::onlyTrashed()->whereIn('id', $ids)->restore();

        if ($restored === 0) {
            return back()->with('error', 'لم يتم استرداد أي تعليق.');
        }

        return back()->with('success', "تم استرداد {$restored} تعليقاً.");
    }

    /** Restore a single soft-deleted comment (row-level button). */
    public function restore(int $comment)
    {
        $model = Comment::withTrashed()->findOrFail($comment);

        $this->authorize('moderate', $model);

        $model->restore();

        return back()->with('success', 'تمت استعادة التعليق.');
    }

    /**
     * Shared status transition: authorize, persist, then announce the change so
     * the comment's author is told what happened.
     */
    private function changeStatus(Request $request, Comment $comment, string $status, string $action, string $message)
    {
        $this->authorize('moderate', $comment);

        $comment->update(['status' => $status]);

        CommentModeratedByAdmin::dispatch($comment, $request->user(), $action);

        return back()->with('success', $message);
    }
}
