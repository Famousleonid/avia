<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $prefs = $user->notification_prefs ?? [];

        // нормализуем
        $prefs = [
            'mute_all' => (bool)($prefs['mute_all'] ?? false),
            'muted_users' => array_values(array_unique(array_map('intval', $prefs['muted_users'] ?? []))),
            'muted_workorders' => array_values(array_unique(array_map('intval', $prefs['muted_workorders'] ?? []))),
        ];

        // список юзеров (без себя) — для UI
        $users = User::query()
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'ok' => true,
            'prefs' => $prefs,
            'users' => $users,
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'mute_all' => ['boolean'],
            'muted_users' => ['array'],
            'muted_users.*' => ['integer', 'exists:users,id'],
            'muted_workorders' => ['array'],
            'muted_workorders.*' => ['integer'],
        ]);

        // нельзя замьютить самого себя
        if (!empty($data['muted_users']) && in_array($user->id, $data['muted_users'], true)) {
            $data['muted_users'] = array_values(array_diff($data['muted_users'], [$user->id]));
        }

        // нормализация
        $data['mute_all'] = (bool)($data['mute_all'] ?? false);
        $data['muted_users'] = array_values(array_unique(array_map('intval', $data['muted_users'] ?? [])));
        $data['muted_workorders'] = array_values(array_unique(array_map('intval', $data['muted_workorders'] ?? [])));

        $user->notification_prefs = $data;
        $user->save();

        return response()->json([
            'ok' => true,
            'prefs' => $user->notification_prefs,
        ]);
    }

}
