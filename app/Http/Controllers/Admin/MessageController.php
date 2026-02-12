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
        if (in_array($fromUser->id, $data['user_ids'])) {
            return response()->json([
                'ok' => false,
                'message' => 'You cannot send message to yourself'
            ], 422);
        }


        $recipients = User::whereIn('id', $data['user_ids'])->get();

        foreach ($recipients as $user) {
            $user->notify(
                new NewMessageNotification(
                    fromUserId: $fromUser->id,
                    fromName: $fromUser->name,
                    text: $data['message']
                )
            );
        }

        return response()->json([
            'ok' => true,
            'message' => 'Message sent successfully'
        ]);
    }
}
