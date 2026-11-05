<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScreenMessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = DB::table('screen_message_recipients as recipients')
            ->join('screen_messages as messages', 'messages.id', '=', 'recipients.screen_message_id')
            ->where('recipients.user_id', $request->user()->id)
            ->whereNull('recipients.dismissed_at')
            ->orderBy('messages.id')
            ->limit(20)
            ->get(['messages.id', 'messages.sender_name', 'messages.message', 'messages.kind', 'messages.created_at'])
            ->map(fn ($item) => [
                'id' => $item->id, 'from_name' => $item->sender_name, 'text' => $item->message,
                'kind' => $item->kind,
                'date' => format_project_date($item->created_at),
                'dismiss_url' => route('messages.screen.dismiss', $item->id),
            ]);
        return response()->json(['items' => $items])->header('Cache-Control', 'no-store, private');
    }

    public function dismiss(Request $request, int $message): JsonResponse
    {
        $recipient = DB::table('screen_message_recipients')
            ->where('user_id', $request->user()->id)->where('screen_message_id', $message);
        abort_unless((clone $recipient)->exists(), 404);
        $recipient->whereNull('dismissed_at')->update(['dismissed_at' => now()]);
        return response()->json(['ok' => true]);
    }
}
