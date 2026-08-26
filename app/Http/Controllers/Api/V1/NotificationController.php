<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

/**
 * API access to the signed-in user's own database notifications — the same
 * records the site's bell reads. Scoped to $request->user() throughout, so one
 * user can never read or modify another's notifications.
 */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->notifications();

        if ($request->boolean('unread')) {
            $query = $request->user()->unreadNotifications();
        }

        $notifications = $query->latest()->paginate($request->integer('per_page', 15));

        return NotificationResource::collection($notifications)
            ->additional([
                'meta' => ['unread_count' => $request->user()->unreadNotifications()->count()],
            ]);
    }

    public function unreadCount(Request $request)
    {
        return response()->json([
            'data' => ['unread_count' => $request->user()->unreadNotifications()->count()],
            'message' => 'OK',
        ]);
    }

    public function read(Request $request, string $notification)
    {
        $model = $request->user()->notifications()->findOrFail($notification);

        $model->markAsRead();

        return response()->json([
            'data' => new NotificationResource($model->fresh()),
            'message' => 'Notification marked as read.',
        ]);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'data' => ['unread_count' => 0],
            'message' => 'All notifications marked as read.',
        ]);
    }
}
