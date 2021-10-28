<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    // show notifications for the logged in user.
    public function show()
    {
        $user = Auth::user();
        $notifications = $user->notifications;
        foreach ($notifications as $notif) {
            $data = $notif->data;
            $data['avatar'] = asset($data['avatar']);
            $notif->data = $data;
        }
        return response()->json(['notifications' => $notifications]);
    }

    public function markAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications()->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    }
}
