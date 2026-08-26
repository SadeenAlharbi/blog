<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * The administrator's own notification inbox, rendered inside the admin shell.
 * It reads the same Laravel database notifications the public site uses — no
 * second notification system.
 */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.notifications.index', [
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function read(Request $request, string $notification)
    {
        $model = $request->user()->notifications()->findOrFail($notification);

        $model->markAsRead();

        $url = $model->data['url'] ?? null;

        return $url ? redirect()->to($url) : back();
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'تم تعليم جميع الإشعارات كمقروءة.');
    }
}
