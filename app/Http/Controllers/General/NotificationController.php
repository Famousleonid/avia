<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Все уведомления (и прочитанные тоже)
        $notifications = $user->notifications()->latest()->paginate(30);

        return view('notifications.index', compact('notifications'));
    }

    public function unreadCount(Request $request)
    {
        $count = $request->user()->unreadNotifications()->count();
        return response()->json(['count' => $count]);
    }

    public function latest(Request $request)
    {
        $user = $request->user();

        $items = $user->notifications()
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'read_at' => $n->read_at,
                    'created_at_human' => optional($n->created_at)->diffForHumans(),
                    'title' => data_get($n->data, 'title', 'Notification'),
                    'message' => data_get($n->data, 'message', ''),
                    'url' => data_get($n->data, 'url', null),
                    'type' => data_get($n->data, 'type', null),
                ];
            });

        return response()->json(['items' => $items]);
    }

    public function markRead(Request $request, string $id)
    {
        $user = $request->user();

        $n = $user->notifications()->where('id', $id)->firstOrFail();
        if (is_null($n->read_at)) {
            $n->markAsRead();
        }

        return response()->json(['ok' => true]);
    }

    public function readAll(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json(['ok' => true]);
    }
}
