<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * Список пользователей для выбора в модалке
     */
    public function users(): JsonResponse
    {
        $me = auth()->id();

        $users = User::query()
            ->withoutReviewAccounts()
            ->where('id', '!=', $me) // себя не показываем
            ->orderBy('name')
            ->get(['id', 'name', 'selection_name_order'])
            ->sortBy(fn (User $user) => mb_strtolower($user->selection_name))
            ->values()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->selection_name,
            ]);

        return response()->json($users);
    }

    /**
     * Отправка сообщения
     */
    public function send(Request $request): JsonResponse
    {
        if (!empty($request->input('group_ids')) || $request->boolean('on_screen')) {
            abort_unless($request->user()->canManageGroupMessages(), 403);
        }
        $data = $request->validate([
            'user_ids'     => ['required_without:group_ids', 'array'],
            'user_ids.*'   => ['integer', 'exists:users,id'],
            'group_ids'    => ['required_without:user_ids', 'array'],
            'group_ids.*'  => ['integer', 'exists:message_groups,id'],
            'on_screen'    => ['sometimes', 'boolean'],
            'screen_kind'  => ['sometimes', 'string', 'in:alert,info,message'],
            'message'      => ['required', 'string', 'max:1000'],
        ]);

        $fromUser = auth()->user();

        // нельзя отправить самому себе
        $directIds = array_map('intval', $data['user_ids'] ?? []);
        if (in_array((int) $fromUser->id, $directIds, true)) {
            return response()->json([
                'ok' => false,
                'message' => 'You cannot send message to yourself'
            ], 422);
        }

        // Snapshot membership at send time; overlapping groups produce one recipient.
        $groupIds = empty($data['group_ids']) ? collect() : DB::table('message_group_user')
            ->whereIn('message_group_id', $data['group_ids'])->pluck('user_id');
        $recipientIds = collect($directIds)->merge($groupIds)->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === (int) $fromUser->id)->unique()->values();

        // грузим получателей вместе с prefs
        $recipients = User::query()
            ->withoutReviewAccounts()
            ->whereIn('id', $recipientIds)
            ->get(['id', 'name', 'selection_name_order', 'notification_prefs']);

        $sent = [];
        $blocked = [];

        foreach ($recipients as $user) {
            $prefs = $user->notification_prefs ?? [];
            $mutedUsers = $prefs['muted_users'] ?? [];

            // если получатель забанил отправителя — не шлём
            if (in_array((int) $fromUser->id, array_map('intval', $mutedUsers), true)) {
                $blocked[] = ['id' => $user->id, 'name' => $user->selection_name];
                continue;
            }

            if (empty($data['on_screen'])) {
                $user->notify(new NewMessageNotification(
                    fromUserId: $fromUser->id,
                    fromName: $fromUser->selection_name,
                    text: $data['message']
                ));
            }

            $sent[] = ['id' => $user->id, 'name' => $user->selection_name];
        }

        if (!empty($data['on_screen']) && count($sent)) {
            DB::transaction(function () use ($fromUser, $data, $sent) {
                $id = DB::table('screen_messages')->insertGetId([
                    'sender_id' => $fromUser->id, 'sender_name' => $fromUser->selection_name,
                    'message' => $data['message'], 'created_at' => now(), 'updated_at' => now(),
                    'kind' => $data['screen_kind'] ?? 'info',
                ]);
                DB::table('screen_message_recipients')->insert(array_map(fn ($user) => [
                    'screen_message_id' => $id, 'user_id' => $user['id'],
                ], $sent));
            });
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
