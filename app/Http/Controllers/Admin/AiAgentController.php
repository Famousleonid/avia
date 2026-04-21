<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiChatMessage;
use App\Models\Workorder;
use App\Services\Ai\AiAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AiAgentController extends Controller
{
    public function chat(Request $request, AiAgentService $service): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'current_context' => ['nullable', 'array'],
            'current_context.current_workorder' => ['nullable', 'array'],
            'current_context.current_workorder.id' => ['nullable', 'integer'],
            'current_context.current_workorder.number' => ['nullable', 'integer'],
            'current_context.current_workorder.manual_id' => ['nullable', 'integer'],
            'current_context.page' => ['nullable', 'array'],
            'current_context.page.route' => ['nullable', 'string', 'max:191'],
            'current_context.origin' => ['nullable', 'string', 'max:128'],
            'current_context.current_manual' => ['nullable', 'array'],
            'current_context.current_manual.number' => ['nullable', 'string', 'max:64'],
            'current_context.current_manual.title' => ['nullable', 'string', 'max:512'],
            'confirm_action' => ['nullable', 'array'],
            'confirm_action.type' => ['nullable', 'string'],
            'confirm_action.tool' => ['nullable', 'string'],
            'confirm_action.payload' => ['nullable', 'array'],
            'confirm_action.ts' => ['nullable', 'integer'],
            'confirm_action.token' => ['nullable', 'string'],
        ]);

        $sessionKey = $request->session()->get('ai_agent_session_key');

        if (! $sessionKey) {
            $sessionKey = (string) Str::uuid();
            $request->session()->put('ai_agent_session_key', $sessionKey);
        }

        $pageContext = (array)($data['current_context'] ?? []);

        // 1) Trust explicit frontend context and store it in session.
        if (!empty($pageContext['current_workorder']['id'])) {
            $request->session()->put('ai_current_workorder_context', [
                'id' => (int)($pageContext['current_workorder']['id'] ?? 0),
                'number' => (int)($pageContext['current_workorder']['number'] ?? 0),
                'manual_id' => (int)($pageContext['current_workorder']['manual_id'] ?? 0),
            ]);
        }

        // 2) Session fallback
        if (empty($pageContext['current_workorder']['id'])) {
            $sessionWo = (array)$request->session()->get('ai_current_workorder_context', []);
            if (!empty($sessionWo['id'])) {
                $pageContext['current_workorder'] = [
                    'id' => (int)($sessionWo['id'] ?? 0),
                    'number' => (int)($sessionWo['number'] ?? 0),
                    'manual_id' => (int)($sessionWo['manual_id'] ?? 0),
                ];
            }
        }

        // 3) Referer fallback: recover from /mains/{id}
        if (empty($pageContext['current_workorder']['id'])) {
            $referer = (string)($request->headers->get('referer') ?? '');
            $path = parse_url($referer, PHP_URL_PATH) ?: '';
            if (preg_match('#/mains/(\d+)$#', $path, $m)) {
                $wo = Workorder::withDrafts()->find((int)$m[1]);
                if ($wo) {
                    $pageContext['current_workorder'] = [
                        'id' => (int)$wo->id,
                        'number' => (int)$wo->number,
                        'manual_id' => (int)($wo->unit?->manual_id ?? 0),
                    ];
                    $request->session()->put('ai_current_workorder_context', $pageContext['current_workorder']);
                }
            }
        }

        try {
            $result = $service->handle(
                user: $request->user(),
                sessionKey: $sessionKey,
                userMessage: $data['message'],
                pageContext: $pageContext,
                confirmAction: (array)($data['confirm_action'] ?? [])
            );

            return response()->json($result);
        } catch (Throwable $e) {
            Log::error('AI agent chat failed', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            $userMessage = $this->aiChatErrorReplyForUser($e);

            return response()->json([
                'ok' => false,
                'reply' => $userMessage,
                'message' => $userMessage,
                'requires_confirmation' => false,
                'action' => null,
            ], 500);
        }
    }

    /**
     * Текст для пользователя: без сырого JSON и stack trace; сбои OpenAI — по-человечески и с юмором.
     */
    protected function aiChatErrorReplyForUser(Throwable $e): string
    {
        if ($this->isOpenAiApiFailure($e)) {
            return $this->randomOpenAiUnavailableMessage();
        }

        if (config('app.debug')) {
            return 'Server error: '.$e->getMessage();
        }

        return 'Server Error';
    }

    protected function isOpenAiApiFailure(Throwable $e): bool
    {
        return str_contains($e->getMessage(), 'OpenAI API error');
    }

    protected function randomOpenAiUnavailableMessage(): string
    {
        $nick = $this->latinAssistantName((string) config('services.openai.agent_nickname', 'Avi'));

        $lines = config('services.openai.unavailable_messages');
        if (! is_array($lines) || $lines === []) {
            return sprintf('%s is temporarily unavailable. Please try again shortly.', $nick);
        }

        $line = $lines[array_rand($lines)];

        return sprintf($line, $nick);
    }

    protected function latinAssistantName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || preg_match('/\p{Cyrillic}/u', $name)) {
            return 'Avi';
        }

        $name = preg_replace('/[^A-Za-z0-9 ._-]/', '', $name) ?? '';
        $name = trim($name);

        return $name !== '' ? $name : 'Avi';
    }

    public function reset(Request $request): JsonResponse
    {
        $sessionKey = $request->session()->get('ai_agent_session_key');

        if ($sessionKey) {
            AiChatMessage::query()
                ->where('user_id', $request->user()->id)
                ->where('session_key', $sessionKey)
                ->delete();
        }

        $newKey = (string) Str::uuid();
        $request->session()->put('ai_agent_session_key', $newKey);

        return response()->json([
            'ok' => true,
            'session_key' => $newKey,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $sessionKey = $request->session()->get('ai_agent_session_key');

        if (! $sessionKey) {
            return response()->json([
                'ok' => true,
                'messages' => [],
            ]);
        }

        $messages = AiChatMessage::query()
            ->where('user_id', $request->user()->id)
            ->where('session_key', $sessionKey)
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('id')
            ->get(['role', 'content']);

        return response()->json([
            'ok' => true,
            'messages' => $messages,
        ]);
    }
}
