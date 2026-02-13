<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(30)
            ->through(function ($n) {
                $d = is_array($n->data) ? $n->data : (json_decode($n->data, true) ?: []);

                return (object)[
                    'id' => $n->id,
                    'read_at' => $n->read_at,
                    'from_name' => $d['from_name'] ?? null,
                    'from_user_id' => $d['from_user_id'] ?? null,
                    'text' => $d['text'] ?? '',
                    'url' => $d['url'] ?? null,
                    'created_at_human' => optional($n->created_at)->diffForHumans(),
                    'created_at' => $n->created_at,
                ];
            });

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

        $items = $user->unreadNotifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($n) {
                $d = is_array($n->data) ? $n->data : (json_decode($n->data, true) ?: []);

                return [
                    'id' => $n->id,
                    'from_name' => $d['from_name'] ?? null,
                    'from_user_id' => $d['from_user_id'] ?? null,
                    'text' => $d['text'] ?? '',
                    'url' => $d['url'] ?? null,
                    'created_at_human' => optional($n->created_at)->diffForHumans(),
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

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();

        $n = $user->notifications()->where('id', $id)->firstOrFail();
        $n->delete();

        return response()->json(['ok' => true]);
    }

}
