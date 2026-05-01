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
                    'from_name' => $d['from_name'] ?? $d['by_user_name'] ?? 'System',
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

        $perPage = (int) $request->get('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        $page = (int) $request->get('page', 1);
        $page = max(1, $page);

        $paginator = $user->unreadNotifications()
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        $items = $paginator->getCollection()->map(function ($n) {
            $d = is_array($n->data) ? $n->data : (json_decode($n->data, true) ?: []);

            return [
                'id' => $n->id,
                'type' => $d['type'] ?? null,
                'event' => $d['event'] ?? null,
                'severity' => $d['severity'] ?? 'info',
                'title' => $d['title'] ?? null,
                'ui' => $d['ui'] ?? [],
                'text' => $d['text'] ?? '',
                'url' => $d['url'] ?? null,
                'from_name' => $d['from_name'] ?? $d['by_user_name'] ?? 'System',
                'created_at_human' => optional($n->created_at)->diffForHumans(),
            ];
        })->values();

        return response()->json([
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'has_more'     => $paginator->hasMorePages(),
                'next_page'    => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
            ],
        ]);
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

    // delete ALL notifications
    public function deleteAll(Request $request)
    {
        $request->user()
            ->notifications()
            ->delete();

        return response()->json([
            'ok' => true
        ]);
    }

}
