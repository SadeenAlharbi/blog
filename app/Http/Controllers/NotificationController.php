<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * In-site notifications (the bell). Backed by Laravel's built-in database
 * notifications on the authenticated user (User uses the Notifiable trait).
 */
class NotificationController extends Controller
{
    /** Full notifications list page ("عرض كل الإشعارات"). */
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark one notification as read, then continue to its target URL.
     * Scoped to the current user's own notifications (findOrFail → 404 otherwise),
     * so a user can never touch someone else's notification.
     */
    public function read(Request $request, string $notification)
    {
        $model = $request->user()->notifications()->findOrFail($notification);

        $model->markAsRead();

        $url = $model->data['url'] ?? null;

        return $url ? redirect()->to($url) : back();
    }

    /** Mark all of the current user's unread notifications as read. */
    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'تم تعليم جميع الإشعارات كمقروءة.');
    }
}
