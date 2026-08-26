<?php

namespace App\Http\Controllers\Admin;

use App\Events\CommentModeratedByAdmin;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
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

        return view('admin.comments.index', [
            'comments' => $query->latest()->paginate(20)->withQueryString(),
            'statuses' => Comment::statuses(),
            'filters' => $request->only(['search', 'status', 'trashed']),
            'counts' => [
                'all' => Comment::count(),
                'approved' => Comment::approved()->count(),
                'hidden' => Comment::hidden()->count(),
                'trashed' => Comment::onlyTrashed()->count(),
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

    /** Restore a soft-deleted comment. */
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
