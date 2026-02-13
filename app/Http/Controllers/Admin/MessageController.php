<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    /**
     * Список пользователей для выбора в модалке
     */
    public function users(): JsonResponse
    {
        $me = auth()->id();

        $users = User::query()
            ->where('id', '!=', $me) // себя не показываем
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($users);
    }

    /**
     * Отправка сообщения
     */
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_ids'     => ['required', 'array', 'min:1'],
            'user_ids.*'   => ['integer', 'exists:users,id'],
            'message'      => ['required', 'string', 'max:1000'],
        ]);

        $fromUser = auth()->user();

        // нельзя отправить самому себе
        if (in_array($fromUser->id, $data['user_ids'], true)) {
            return response()->json([
                'ok' => false,
                'message' => 'You cannot send message to yourself'
            ], 422);
        }

        // грузим получателей вместе с prefs
        $recipients = User::query()
            ->whereIn('id', $data['user_ids'])
            ->get(['id', 'name', 'notification_prefs']);

        $sent = [];
        $blocked = [];

        foreach ($recipients as $user) {
            $prefs = $user->notification_prefs ?? [];
            $mutedUsers = $prefs['muted_users'] ?? [];

            // если получатель забанил отправителя — не шлём
            if (in_array($fromUser->id, $mutedUsers, true)) {
                $blocked[] = ['id' => $user->id, 'name' => $user->name];
                continue;
            }

            $user->notify(new NewMessageNotification(
                fromUserId: $fromUser->id,
                fromName: $fromUser->name,
                text: $data['message']
            ));

            $sent[] = ['id' => $user->id, 'name' => $user->name];
        }

        // если НЕ отправилось никому — можно вернуть 403/422, но лучше 200 с ok=true/false по твоему вкусу
        $ok = count($sent) > 0;

        return response()->json([
            'ok' => $ok,
            'message' => $ok ? 'Message sent' : 'Message was not sent to any user',
            'sent' => $sent,
            'blocked' => $blocked,
            'blocked_message' => count($blocked)
                ? 'Some users blocked you and did not receive your message.'
                : null,
        ]);
    }

}
