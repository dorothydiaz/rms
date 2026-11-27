<?php

namespace App\Http\Controllers;

use App\Models\Hr\InternalNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Mark a single internal notification as read for the authenticated user.
     */
    public function markRead(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $notif = InternalNotification::forUser($user)->where('id', $id)->first();
        if (!$notif) {
            if (InternalNotification::where('id', $id)->exists()) {
                abort(403, 'Unauthorized notification access.');
            }
            abort(404);
        }

        if (!$notif->is_read) {
            $notif->is_read = true;
            $notif->read_at = now();
            $notif->save();
        }

        $unreadCount = InternalNotification::forUser($user)
            ->where('is_read', false)
            ->count();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'id' => $notif->id,
                'unread_count' => $unreadCount,
                'link' => $notif->link,
            ]);
        }

        if ($notif->link) {
            return redirect($notif->link);
        }

        return redirect()->back();
    }

    /**
     * Mark all unread notifications for the authenticated user as read.
     */
    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        InternalNotification::forUser($user)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
            ]);
        }

        return redirect()->back();
    }
}

