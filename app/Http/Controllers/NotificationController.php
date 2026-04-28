<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for authenticated user
     */
    public function index(Request $request)
    {
        $per_page = $request->get('per_page', 10);
        /** @var \App\Models\User */
        $user = auth('sanctum')->user();
        $query = Notification::where('notifiable_id', $user->id)->latest('created_at');

        return response()->json([
            'notifications' => $query->paginate($per_page),
            'unread_count' => $user->unreadNotifications->count(),
        ]);
    }

    /**
     * Get only unread notifications
     */
    public function unread(Request $request)
    {
        $per_page = $request->get('per_page', 10);
        /** @var \App\Models\User */
        $user = auth('sanctum')->user();
        $query = Notification::where('notifiable_id', $user->id);


        return response()->json([
            'notifications' => $query->paginate($per_page),
            'count' => $user->unreadNotifications->count(),
        ]);
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead($id)
    {
        /** @var \App\Models\User */
        $user = auth('sanctum')->user();

        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        /** @var \App\Models\User */
        $user = auth('sanctum')->user();

        $user->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        /** @var \App\Models\User */
        $user = auth('sanctum')->user();

        $user->notifications()->findOrFail($id)->delete();

        return response()->json([
            'message' => 'Notification deleted'
        ]);
    }

    /**
     * Delete all read notifications
     */
    public function clearRead()
    {
        /** @var \App\Models\User */
        $user = auth('sanctum')->user();

        $user->readNotifications()->delete();

        return response()->json([
            'message' => 'Read notifications cleared'
        ]);
    }
}
