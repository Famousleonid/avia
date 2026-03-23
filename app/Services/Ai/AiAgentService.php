<?php

namespace App\Services\Ai;

use App\Models\AiChatMessage;
use App\Models\User;
use App\Services\Ai\Tools\AnalyzeWorkorderTool;
use App\Services\Ai\Tools\CreateWorkorderNoteTool;
use App\Services\Ai\Tools\FindWorkorderTool;
use App\Services\Ai\Tools\LookupWorkorderPartsTool;
use App\Services\Ai\Tools\SearchWorkordersTool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiAgentService
{
    public function __construct(
        protected FindWorkorderTool $findWorkorderTool,
        protected AnalyzeWorkorderTool $analyzeWorkorderTool,
        protected CreateWorkorderNoteTool $createWorkorderNoteTool,
        protected LookupWorkorderPartsTool $lookupWorkorderPartsTool,
        protected SearchWorkordersTool $searchWorkordersTool,
    ) {
    }

    public function handle(
        User $user,
        string $sessionKey,
        string $userMessage,
        array $pageContext = [],
        array $confirmAction = []
    ): array
    {
        if (!empty($confirmAction)) {
            $result = $this->executeConfirmedAction($user, $sessionKey, $confirmAction);
            $this->storeMessage($user->id, $sessionKey, 'assistant', $result['reply']);
            return $result;
        }

        $this->storeMessage($user->id, $sessionKey, 'user', $userMessage, null, [
            'page_context' => $pageContext,
        ]);

        $history = $this->buildConversationHistory($user->id, $sessionKey);

        $tools = [
            $this->findWorkorderTool->schema(),
            $this->searchWorkordersTool->schema(),
            $this->analyzeWorkorderTool->schema(),
            $this->createWorkorderNoteTool->schema(),
            $this->lookupWorkorderPartsTool->schema(),
        ];

        $systemPrompt = $this->systemPrompt($user, $pageContext);

        $response = $this->callOpenAi(
            input: array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $history
            ),
            tools: $tools
        );

        $resolved = $this->resolveResponseWithTools(
            user: $user,
            sessionKey: $sessionKey,
            initialResponse: $response,
            originalInput: array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $history
            ),
            tools: $tools,
            pageContext: $pageContext
        );

        $this->storeMessage($user->id, $sessionKey, 'assistant', $resolved['text']);

        return [
            'ok' => true,
            'reply' => $resolved['text'],
            'requires_confirmation' => (bool)($resolved['requires_confirmation'] ?? false),
            'action' => $resolved['action'] ?? null,
        ];
    }

    protected function resolveResponseWithTools(
        User $user,
        string $sessionKey,
        array $initialResponse,
        array $originalInput,
        array $tools,
        array $pageContext = []
    ): array {
        $response = $initialResponse;

        for ($i = 0; $i < 5; $i++) {
            $toolCalls = $this->extractToolCalls($response);

            if (empty($toolCalls)) {
                return ['text' => $this->extractOutputText($response)];
            }

            $toolOutputs = [];

            foreach ($toolCalls as $call) {
                $toolName = $call['name'];
                $arguments = $call['arguments'];
                $callId = $call['call_id'];

                $toolResult = $this->runTool($user, $toolName, $arguments, $pageContext);

                if (!empty($toolResult['requires_confirmation'])) {
                    $signedAction = $this->buildSignedAction(
                        $user,
                        $sessionKey,
                        $toolName,
                        (array)($toolResult['action'] ?? [])
                    );

                    return [
                        'text' => (string)($toolResult['message'] ?? 'Please confirm this action.'),
                        'requires_confirmation' => true,
                        'action' => $signedAction,
                    ];
                }

                $this->storeMessage(
                    $user->id,
                    $sessionKey,
                    'tool',
                    json_encode($toolResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                    $toolName,
                    [
                        'call_id' => $callId,
                        'arguments' => $arguments,
                    ]
                );

                $toolOutputs[] = [
                    'type' => 'function_call_output',
                    'call_id' => $callId,
                    'output' => json_encode($toolResult, JSON_UNESCAPED_UNICODE),
                ];
            }

            $response = $this->callOpenAi(
                input: $toolOutputs ?? [],
                tools: $tools,
                previousResponseId: (string)($response['id'] ?? '')
            );
        }

        return ['text' => 'I could not complete the request safely.'];
    }

    protected function runTool(User $user, string $toolName, array $arguments, array $pageContext = []): array
    {
        $ctxWo = (array)($pageContext['current_workorder'] ?? []);
        $ctxWorkorderId = (int)($ctxWo['id'] ?? 0);

        // convenience defaults: if user asks about "current workorder",
        // model can omit workorder_id and we will provide it from page context
        if ($ctxWorkorderId > 0 && empty($arguments['workorder_id'])) {
            if (in_array($toolName, ['analyzeWorkorder', 'createWorkorderNote', 'lookupWorkorderParts'], true)) {
                $arguments['workorder_id'] = $ctxWorkorderId;
            }
        }

        return match ($toolName) {
            'findWorkorder' => $this->findWorkorderTool->run($user, $arguments),
            'searchWorkorders' => $this->searchWorkordersTool->run($user, $arguments),
            'analyzeWorkorder' => $this->analyzeWorkorderTool->run($user, $arguments),
            'createWorkorderNote' => $this->createWorkorderNoteTool->run($user, $arguments),
            'lookupWorkorderParts' => $this->lookupWorkorderPartsTool->run($user, $arguments),
            default => [
                'ok' => false,
                'message' => "Unknown tool: {$toolName}",
            ],
        };
    }

    protected function callOpenAi(array $input, array $tools, ?string $previousResponseId = null): array
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.model', 'gpt-5.4');

        $payload = [
            'model' => $model,
            'input' => $input,
            'tools' => $tools,
        ];
        if (!empty($previousResponseId)) {
            $payload['previous_response_id'] = $previousResponseId;
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->post('https://api.openai.com/v1/responses', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI API error: ' . $response->body());
        }

        return $response->json();
    }

    protected function extractToolCalls(array $response): array
    {
        $result = [];

        foreach (($response['output'] ?? []) as $item) {
            if (($item['type'] ?? null) === 'function_call') {
                $args = json_decode($item['arguments'] ?? '{}', true);
                if (! is_array($args)) {
                    $args = [];
                }

                $result[] = [
                    'call_id' => $item['call_id'] ?? Str::uuid()->toString(),
                    'name' => $item['name'] ?? '',
                    'arguments' => $args,
                ];
            }
        }

        return $result;
    }

    protected function extractOutputText(array $response): string
    {
        if (! empty($response['output_text'])) {
            return trim((string)$response['output_text']);
        }

        $chunks = [];

        foreach (($response['output'] ?? []) as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach (($item['content'] ?? []) as $content) {
                if (($content['type'] ?? null) === 'output_text') {
                    $chunks[] = $content['text'] ?? '';
                }
            }
        }

        return trim(implode("\n", $chunks)) ?: 'No response.';
    }

    protected function buildConversationHistory(int $userId, string $sessionKey): array
    {
        $messages = AiChatMessage::query()
            ->where('user_id', $userId)
            ->where('session_key', $sessionKey)
            ->whereIn('role', ['user', 'assistant'])
            ->latest('id')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        return $messages->map(function (AiChatMessage $message) {
            return [
                'role' => $message->role,
                'content' => $message->content,
            ];
        })->all();
    }

    protected function storeMessage(
        int $userId,
        string $sessionKey,
        string $role,
        ?string $content,
        ?string $toolName = null,
        ?array $meta = null
    ): void {
        AiChatMessage::create([
            'user_id' => $userId,
            'session_key' => $sessionKey,
            'role' => $role,
            'tool_name' => $toolName,
            'content' => $content,
            'meta' => $meta,
        ]);
    }

    protected function systemPrompt(User $user, array $pageContext = []): string
    {
        $currentWo = $pageContext['current_workorder'] ?? null;
        $contextLine = '';
        if (is_array($currentWo) && !empty($currentWo['id'])) {
            $woNo = (int)($currentWo['number'] ?? 0);
            $manualId = (int)($currentWo['manual_id'] ?? 0);
            $contextLine = "Current page context (internal): WO number {$woNo}, manual_id {$manualId}. When talking to the user, refer to the workorder only by WO number — never mention internal database IDs.";
        }

        $pageRoute = $pageContext['page']['route'] ?? null;
        if (is_string($pageRoute) && $pageRoute !== '') {
            $contextLine .= ($contextLine !== '' ? "\n" : '')
                . "Current browser screen (Laravel route name): «{$pageRoute}». Use this with the UI NAVIGATION MAP to tailor steps (e.g. user already on mains.show vs workorders.index).";
        }

        $uiNavigationBlock = $this->buildUiNavigationHelpBlock();

        $fullName = trim((string)($user->name ?? ''));
        $firstName = $this->userFirstName($user);
        $addressHint = $fullName !== ''
            ? "The logged-in user's name is «{$fullName}». Address them by first name «{$firstName}» in every reply (use it naturally: greeting, transitions, closing — not in every single sentence)."
            : 'The user has no name on file; address them neutrally (e.g. «коллега» / «colleague»).';

        $agentName = trim((string)config('services.openai.agent_name', 'Assistant'));
        if ($agentName === '') {
            $agentName = 'Assistant';
        }

        return <<<PROMPT
You are «{$agentName}», the AI assistant for an aviation maintenance workshop (workorders, tasks, manuals, photos, damage notes). Do not mention Laravel, PHP, frameworks, or programming stack to the user unless they explicitly ask about IT internals.
Introduce yourself by this name when appropriate (first greeting, if asked who you are, or when it fits naturally). Do not use a different name or title.

Personalization (mandatory):
{$addressHint}
- Match the user's language (Russian ↔ English) for your sentences and explanations.
- Names vs language: if the user writes in **English** but their first name is in **Cyrillic**, address them with a **normal Latin transliteration** of that first name (e.g. Иван → Ivan, Мария → Maria, Дмитрий → Dmitry) so English reads naturally; do not leave unexplained Cyrillic mid-sentence unless the user prefers otherwise. If the user writes in **Russian**, use the **exact Cyrillic** first name from the profile.
- Occasionally (roughly every 3–5 messages, not every time), add one short warm line: a light compliment or a playful note about their first name — etymology can match the language of the reply — keep it tasteful, professional, one sentence max, never flirty or intrusive. Skip if it would feel odd (errors, serious safety topics).

Your goals:
1. Help with workorders and related data only through the tools you have (see below).
2. Answer general questions like a normal helpful assistant when no system data is needed.
3. Use tools only when real system data is needed.
4. Never invent system data.
5. Never perform write actions unless the user explicitly asked for it and clearly confirmed it.
6. Explain results in simple human language.
7. Keep answers concise and practical.

What you can actually do in THIS app (strict — if the user asks «what can you do» / «что ты умеешь», list ONLY this; do not add features from imagination or generic chatbot abilities):
- findWorkorder: find one workorder by WO number (read-only). Never tell the user internal row ids.
- searchWorkorders: partial search only on workorder fields: number, user_id, unit_id, instruction_id, open_at, customer_id, approve, approve_at, description, manual, serial_number, customer_po, modified, is_draft; return links to open the main page.
- analyzeWorkorder: task progress, closed = isDone(); status/step = first unfinished general task stage by sort_order; photos do not affect status or closed state.
- createWorkorderNote: propose appending a note to a workorder — only after explicit user intent and UI confirmation (not instant).
- lookupWorkorderParts: look up manual/parts lines for a workorder (read-only).
- UI navigation help: explain where to click in the admin interface using ONLY the «UI NAVIGATION MAP» block below (no tools; no invented menus).
- Plus: plain-language conversation without accessing the database when no tool is needed.

Do NOT claim you can: upload or delete files or photos, send email or notifications, edit workorders/tasks in bulk, change statuses or approve by yourself, export PDF/Excel, run reports, replace procedures, access data you cannot fetch with tools, or any other feature not listed above.

Communication style (strict):
- Never mention or write internal workorder database IDs to the user — only WO number (номер воркордера) and human-readable facts.
- Speak only in plain human language (Russian or English, matching the user). No SQL, no programming code, no database queries, no framework names, no technical dumps.
- Do not show raw JSON, stack traces, or API payloads to the user; summarize what they mean in words.
- If the user asks how something works technically, explain the idea in simple terms without code. If they explicitly ask for code/SQL, briefly refuse in a friendly way and describe what to do in the UI or in general terms.

Important behavior:
- If the question is general, answer directly without tools.
- If the question needs real data from the system, use tools.
- If the user wants a list of workorders matching text, use searchWorkorders (only the allowed workorder fields above) and give each result as a markdown link [label](url). Missing photos does not affect workorder status or whether it is closed.
- If the user asks to create or modify something, first confirm details.
- If a tool returns an error, explain it plainly in human language.
- For write actions, request explicit UI confirmation first. Never execute write action immediately after tool proposal.

System context:
- «Status / step» of a workorder for the user = on which unfinished **general task** stage it sits; order of stages is `general_tasks.sort_order`. Closed workorder = `isDone()` (Completed task finished). Photos do not change status or closed state.
- Draft / approve fields exist on the workorder; do not equate photos with progress.
- User roles may include Admin, Manager, Technician.
{$contextLine}

{$uiNavigationBlock}

PROMPT;
    }

    /**
     * First word of name for natural address (supports "Иван Петров" → "Иван").
     */
    protected function userFirstName(User $user): string
    {
        $name = trim((string)($user->name ?? ''));
        if ($name === '') {
            return '';
        }
        $parts = preg_split('/\s+/u', $name, 2);

        return $parts[0] ?? $name;
    }

    /**
     * Текстовая «карта» интерфейса для подсказок «куда нажать» (config/ui_navigation_help.php).
     */
    protected function buildUiNavigationHelpBlock(): string
    {
        $cfg = config('ui_navigation_help', []);
        if (! is_array($cfg) || $cfg === []) {
            return '';
        }

        $sidebarRu = trim((string)($cfg['sidebar']['ru'] ?? ''));
        $sidebarEn = trim((string)($cfg['sidebar']['en'] ?? ''));
        $woRu = trim((string)($cfg['workorder_main_page']['ru'] ?? ''));
        $woEn = trim((string)($cfg['workorder_main_page']['en'] ?? ''));
        $rulesRu = trim((string)($cfg['rules']['ru'] ?? ''));
        $rulesEn = trim((string)($cfg['rules']['en'] ?? ''));

        if ($sidebarRu === '' && $sidebarEn === '') {
            return '';
        }

        $out = [];
        $out[] = '=== UI NAVIGATION MAP (authoritative — for questions like «where do I click», «how do I get to…»; match the user\'s language; never invent menu items outside this map) ===';
        $out[] = '';
        $out[] = '--- Russian (Русский) ---';
        $out[] = $sidebarRu;
        $out[] = '';
        $out[] = $woRu;
        $out[] = '';
        $out[] = $rulesRu;
        $out[] = '';
        $out[] = '--- English ---';
        $out[] = $sidebarEn;
        $out[] = '';
        $out[] = $woEn;
        $out[] = '';
        $out[] = $rulesEn;

        return implode("\n", array_filter($out, static fn ($line) => $line !== null));
    }

    protected function buildSignedAction(User $user, string $sessionKey, string $toolName, array $action): array
    {
        $ts = now()->timestamp;
        $payload = [
            'type' => (string)($action['type'] ?? ''),
            'tool' => $toolName,
            'payload' => (array)($action['payload'] ?? []),
            'ts' => $ts,
        ];

        $base = [
            'user_id' => $user->id,
            'session_key' => $sessionKey,
            'type' => $payload['type'],
            'tool' => $payload['tool'],
            'payload' => $payload['payload'],
            'ts' => $payload['ts'],
        ];

        $payload['token'] = hash_hmac('sha256', json_encode($base, JSON_UNESCAPED_UNICODE), (string)config('app.key'));
        return $payload;
    }

    protected function executeConfirmedAction(User $user, string $sessionKey, array $confirmAction): array
    {
        $type = (string)($confirmAction['type'] ?? '');
        $tool = (string)($confirmAction['tool'] ?? '');
        $payload = (array)($confirmAction['payload'] ?? []);
        $ts = (int)($confirmAction['ts'] ?? 0);
        $token = (string)($confirmAction['token'] ?? '');

        if ($type === '' || $tool === '' || $ts <= 0 || $token === '') {
            return ['ok' => false, 'reply' => 'Invalid confirmation action.'];
        }

        if (now()->timestamp - $ts > 15 * 60) {
            return ['ok' => false, 'reply' => 'Confirmation expired. Please ask again.'];
        }

        $base = [
            'user_id' => $user->id,
            'session_key' => $sessionKey,
            'type' => $type,
            'tool' => $tool,
            'payload' => $payload,
            'ts' => $ts,
        ];
        $expected = hash_hmac('sha256', json_encode($base, JSON_UNESCAPED_UNICODE), (string)config('app.key'));

        if (!hash_equals($expected, $token)) {
            return ['ok' => false, 'reply' => 'Confirmation token mismatch.'];
        }

        $result = match ($type) {
            'create_workorder_note' => $this->createWorkorderNoteTool->executeConfirmed($user, $payload),
            default => ['ok' => false, 'message' => 'Unsupported confirmation action.'],
        };

        return [
            'ok' => (bool)($result['ok'] ?? false),
            'reply' => (string)($result['message'] ?? 'Action completed.'),
            'requires_confirmation' => false,
            'action' => null,
        ];
    }
}
