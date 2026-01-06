<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for authenticated user
     */
    public function index()
    {
        $user = auth()->user();

        return response()->json([
            'notifications' => $user->notifications,
            'unread' => $user->unreadNotifications,
            'unread_count' => $user->unreadNotifications->count(),
        ]);
    }

    /**
     * Get only unread notifications
     */
    public function unread()
    {
        return response()->json([
            'notifications' => auth()->user()->unreadNotifications,
            'count' => auth()->user()->unreadNotifications->count(),
        ]);
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
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
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        auth()->user()->notifications()->findOrFail($id)->delete();

        return response()->json([
            'message' => 'Notification deleted'
        ]);
    }

    /**
     * Delete all read notifications
     */
    public function clearRead()
    {
        auth()->user()->readNotifications()->delete();

        return response()->json([
            'message' => 'Read notifications cleared'
        ]);
    }
}
