<?php

namespace App\Services\Ai;

use App\Models\AiChatMessage;
use App\Models\Manual;
use App\Models\User;
use App\Services\Ai\Tools\AnalyzeWorkorderTool;
use App\Services\Ai\Tools\CountWorkorderImagesTool;
use App\Services\Ai\Tools\CreateWorkorderNoteTool;
use App\Services\Ai\Tools\FindWorkorderTool;
use App\Services\Ai\Tools\LookupManualEditPermissionsTool;
use App\Services\Ai\Tools\LookupSerialNumberTool;
use App\Services\Ai\Tools\LookupWorkorderPartsTool;
use App\Services\Ai\Tools\ListManualRevisionChecksDueTool;
use App\Services\Ai\Tools\SearchMyWorkordersByOpenProcessTool;
use App\Services\Ai\Tools\SearchActivityLogsTool;
use App\Services\Ai\Tools\SearchWorkordersByOpenProcessTool;
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
        protected SearchMyWorkordersByOpenProcessTool $searchMyWorkordersByOpenProcessTool,
        protected SearchWorkordersByOpenProcessTool $searchWorkordersByOpenProcessTool,
        protected SearchActivityLogsTool $searchActivityLogsTool,
        protected LookupManualEditPermissionsTool $lookupManualEditPermissionsTool,
        protected ListManualRevisionChecksDueTool $listManualRevisionChecksDueTool,
        protected CountWorkorderImagesTool $countWorkorderImagesTool,
        protected LookupSerialNumberTool $lookupSerialNumberTool,
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

        if ($serialReply = $this->tryHandleSerialLookup($user, $userMessage)) {
            $this->storeMessage($user->id, $sessionKey, 'assistant', $serialReply);

            return [
                'ok' => true,
                'reply' => $serialReply,
                'requires_confirmation' => false,
                'action' => null,
            ];
        }

        $history = $this->buildConversationHistory($user->id, $sessionKey);

        $tools = $this->toolsForUser($user);

        $systemPrompt = $this->systemPrompt($user, $pageContext, $userMessage);

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

    /**
     * Tools that are safe for every signed-in role. Each tool still applies its
     * own workorder policy, so users only see records they may view or update.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function toolsForUser(User $user): array
    {
        $tools = [
            $this->findWorkorderTool->schema(),
            $this->analyzeWorkorderTool->schema(),
            $this->searchMyWorkordersByOpenProcessTool->schema(),
            $this->createWorkorderNoteTool->schema(),
            $this->lookupWorkorderPartsTool->schema(),
            $this->countWorkorderImagesTool->schema(),
            $this->lookupSerialNumberTool->schema(),
        ];

        if ($user->isSystemAdmin()) {
            $tools = array_merge($tools, [
                $this->searchWorkordersTool->schema(),
                $this->searchWorkordersByOpenProcessTool->schema(),
                $this->searchActivityLogsTool->schema(),
                $this->lookupManualEditPermissionsTool->schema(),
                $this->listManualRevisionChecksDueTool->schema(),
            ]);
        }

        return $tools;
    }

    /** @return array<int, string> */
    protected function allowedToolNames(User $user): array
    {
        return collect($this->toolsForUser($user))
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
    }

    protected function capabilityPromptBlock(User $user): string
    {
        $capabilities = [
            '- findWorkorder: find one workorder by WO number (read-only). Never tell the user internal row ids.',
            '- analyzeWorkorder: task progress, closed = isDone(); status/step = first unfinished general task stage by sort_order; photos do not affect status or closed state.',
            '- searchMyWorkordersByOpenProcess: find only current user\'s workorders with a started but unfinished process; optional process-name filter; return links to open the main page.',
            '- createWorkorderNote: propose appending a note to a workorder — only after explicit user intent and UI confirmation (not instant).',
            '- lookupWorkorderParts: look up manual/parts lines for a workorder (read-only).',
            '- countWorkorderImages: count images/photos for one visible workorder, list top visible workorders with the most images/photos, or return the total across visible workorders (read-only).',
            '- lookupSerialNumber: find a serial number / S/N across visible workorders and related records, and tell which WO it belongs to.',
        ];

        if ($user->isSystemAdmin()) {
            $capabilities = array_merge($capabilities, [
                '- searchWorkorders: partial search on all workorder columns (except internal id) plus related customer, unit (+ manual), instruction, assigned user; return links to open the main page.',
                '- searchWorkordersByOpenProcess: find all visible workorders with open process rows; optional customer and process filters; return links to open the main page.',
                '- searchActivityLogs: System Admin-only thorough read-only audit search. Find who created, changed, or deleted records and when; combine WO, CMM manual number, P/N, actor, event, log category, area, free text, and date range. It searches the full matching log history, including TDR Add Part, manual Parts, ordinary Parts/TDR references, and Bushing before/after snapshots.',
                '- lookupManualEditPermissions: which CMM manuals a user may edit, who may edit a manual, manuals with responsible users, and manual number ↔ LIB mapping; read-only.',
                '- listManualRevisionChecksDue: show CMM manuals whose revision check is overdue or due within X days; read-only.',
            ]);
        }

        return implode("\n", array_merge($capabilities, [
            '- UI navigation help: explain where to click in the admin interface using ONLY the «UI NAVIGATION MAP» block below (no tools; no invented menus).',
            '- Plus: plain-language conversation without accessing the database when no tool is needed.',
        ]));
    }

    protected function roleSpecificBehaviorPrompt(User $user): string
    {
        if (! $user->isSystemAdmin()) {
            return '- Use only the functions listed above for this role. Do not offer, describe, or attempt restricted administrative or audit capabilities.';
        }

        return <<<'PROMPT'
- If the user wants a list of workorders matching text, use searchWorkorders (all WO fields + related customer/unit/instruction/user). Format each line as: `[WO <number>](open_url) — description…` (link text = WO number only). Optionally add a second markdown link to open the Workorder table with search pre-filled if origin is in context (`…/workorders?q=…`). Missing photos does not affect workorder status or whether it is closed.
- If the user asks who/when created, added, changed, removed, or deleted something, asks for an audit trail/history/logs, or combines audit criteria such as WO + CMM + P/N + person + date, use searchActivityLogs. Pass every supplied criterion because filters are combined with AND; search the full matching history before answering. For a TDR Add Part search, use `workorder_number`, `area: components`, and `event: created`; add P/N when supplied. For manual/CMM Parts, use `manual_number`, `area: components`, and normally `event: created`. Use exact P/N matching unless a partial search is explicitly requested. For current WO/CMM, use its human number from context. Report actor, date/time, event, WO/CMM number, P/N, and changed fields when present; never expose internal ids. If no match exists, say so plainly and do not guess.
- If the user asks which manuals/CMMs need revision checks soon, are due, overdue, or asks for top 10/15/20 manuals with less than X days before revision check, use listManualRevisionChecksDue. Format each result as `[<manual_number>](manual_url) — <title>, rev <last_revision_number if present>, last check <last_checked_at or never>, due <next_due_at>, <days_until_due> days`.
- For `lookupManualEditPermissions` about the manual on screen: use the **CMM number** from context (e.g. 32-21-09) as `manual_number`; in the answer, speak only in terms of that CMM number — never `manual_id`.
PROMPT;
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
        if (! in_array($toolName, $this->allowedToolNames($user), true)) {
            return [
                'ok' => false,
                'message' => 'This assistant function is not available for your role.',
            ];
        }

        $ctxWo = (array)($pageContext['current_workorder'] ?? []);
        $ctxWorkorderId = (int)($ctxWo['id'] ?? 0);

        // convenience defaults: if user asks about "current workorder",
        // model can omit workorder_id and we will provide it from page context
        if ($ctxWorkorderId > 0 && empty($arguments['workorder_id'])) {
            if (
                $toolName === 'countWorkorderImages'
                && ($arguments['mode'] ?? null) === 'total'
            ) {
                // Keep global image totals global even when the browser is on a specific WO.
            } elseif (in_array($toolName, ['analyzeWorkorder', 'createWorkorderNote', 'lookupWorkorderParts', 'countWorkorderImages'], true)) {
                $arguments['workorder_id'] = $ctxWorkorderId;
            }
        }

        return match ($toolName) {
            'findWorkorder' => $this->findWorkorderTool->run($user, $arguments),
            'searchWorkorders' => $this->searchWorkordersTool->run($user, $arguments),
            'analyzeWorkorder' => $this->analyzeWorkorderTool->run($user, $arguments),
            'searchMyWorkordersByOpenProcess' => $this->searchMyWorkordersByOpenProcessTool->run($user, $arguments),
            'searchWorkordersByOpenProcess' => $this->searchWorkordersByOpenProcessTool->run($user, $arguments),
            'searchActivityLogs' => $this->searchActivityLogsTool->run($user, $arguments),
            'createWorkorderNote' => $this->createWorkorderNoteTool->run($user, $arguments),
            'lookupWorkorderParts' => $this->lookupWorkorderPartsTool->run($user, $arguments),
            'lookupManualEditPermissions' => $this->lookupManualEditPermissionsTool->run($user, $arguments),
            'listManualRevisionChecksDue' => $this->listManualRevisionChecksDueTool->run($user, $arguments),
            'countWorkorderImages' => $this->countWorkorderImagesTool->run($user, $arguments),
            'lookupSerialNumber' => $this->lookupSerialNumberTool->run($user, $arguments),
            default => [
                'ok' => false,
                'message' => "Unknown tool: {$toolName}",
            ],
        };
    }

    protected function tryHandleSerialLookup(User $user, string $userMessage): ?string
    {
        $serial = $this->extractSerialLookupCandidate($userMessage);
        if ($serial === null) {
            return null;
        }

        $result = $this->lookupSerialNumberTool->run($user, [
            'serial_number' => $serial,
            'limit' => 20,
        ]);

        return $this->formatSerialLookupReply($userMessage, $serial, $result);
    }

    protected function extractSerialLookupCandidate(string $message): ?string
    {
        $text = trim($message);
        if ($text === '') {
            return null;
        }

        $serialLabel = '(?<![A-Za-z0-9._\/-])(?:s\/?n|sn|serial(?:\s+number)?|серийн\p{L}*(?:\s+номер)?|серийник)(?![A-Za-z0-9._\/-])';
        $hasSerialIntent = (bool) preg_match('/'.$serialLabel.'/ui', $text);
        $hasLogCardIntent = (bool) preg_match('/\b(?:log\s*card|rdrs?)\b/ui', $text);
        if (! $hasSerialIntent) {
            if (! $hasLogCardIntent) {
                return null;
            }

            $logCardCandidate = $this->bestSerialLookupToken($text);
            return $logCardCandidate !== '' ? $logCardCandidate : null;
        }

        $filler = '(?:part|component|detail|details|детал[ьи]|компонент\p{L}*)';
        if (preg_match('/'.$serialLabel.'\s*[:#№-]?\s*(?:'.$filler.'\s*)?[:#№-]?\s*([A-Za-z0-9][A-Za-z0-9._\/-]{0,80})/ui', $text, $match)) {
            $candidate = $this->cleanSerialLookupCandidate((string) $match[1]);
            if ($candidate !== '') {
                $tokenCandidate = $this->bestSerialLookupToken($text);
                if (
                    $tokenCandidate !== ''
                    && strlen($tokenCandidate) > strlen($candidate)
                    && str_contains(mb_strtolower($tokenCandidate), mb_strtolower($candidate))
                ) {
                    return $tokenCandidate;
                }

                return $candidate;
            }
        }

        $tokenCandidate = $this->bestSerialLookupToken($text);
        if ($tokenCandidate !== '') {
            return $tokenCandidate;
        }

        return null;
    }

    protected function bestSerialLookupToken(string $text): string
    {
        if (! preg_match_all('/[A-Za-z0-9][A-Za-z0-9._\/-]{0,80}/u', $text, $matches)) {
            return '';
        }

        $skip = ['sn', 's', 'n', 'serial', 'number', 'find', 'lookup', 'part', 'component', 'detail', 'details'];
        $tokens = array_values(array_filter($matches[0], function (string $token) use ($skip) {
            $normalized = mb_strtolower(trim($token, " \t\n\r\0\x0B:;,.!?()[]{}"));

            return $normalized !== '' && ! in_array($normalized, $skip, true);
        }));

        return $tokens === []
            ? ''
            : $this->cleanSerialLookupCandidate((string) end($tokens));
    }

    protected function cleanSerialLookupCandidate(string $candidate): string
    {
        return trim($candidate, " \t\n\r\0\x0B:;,.!?()[]{}<>\"'");
    }

    protected function formatSerialLookupReply(string $userMessage, string $serial, array $result): string
    {
        $isRussian = (bool) preg_match('/\p{Cyrillic}/u', $userMessage);
        $matches = collect($result['matches'] ?? []);

        if (! ($result['ok'] ?? false)) {
            return $isRussian
                ? "Не получилось выполнить поиск по S/N {$serial}: ".(string)($result['message'] ?? 'ошибка поиска.')
                : "Could not search S/N {$serial}: ".(string)($result['message'] ?? 'search error.');
        }

        if ($matches->isEmpty()) {
            return $isRussian
                ? "По S/N {$serial} совпадений не найдено."
                : "No matches found for S/N {$serial}.";
        }

        $lines = [$isRussian ? "Нашёл по S/N {$serial}:" : "Found matches for S/N {$serial}:"];

        foreach ($matches as $match) {
            $woNumber = $match['workorder_number'] ?? null;
            $url = $match['open_url'] ?? null;
            $woText = $woNumber
                ? ($url ? "[WO {$woNumber}]({$url})" : "WO {$woNumber}")
                : ($isRussian ? 'WO не привязан' : 'No linked WO');

            $details = array_filter([
                $match['source'] ?? null,
                $match['part_name'] ?? null,
                ! empty($match['part_number']) ? 'P/N '.$match['part_number'] : null,
                ! empty($match['ipl_num']) ? 'IPL '.$match['ipl_num'] : null,
                ! empty($match['serial_number']) ? 'S/N '.$match['serial_number'] : null,
                ($match['match_type'] ?? null) === 'partial' ? ($isRussian ? 'частичное совпадение' : 'partial match') : null,
                $match['note'] ?? null,
            ]);

            $lines[] = '- '.$woText.' — '.implode(', ', $details);
        }

        return implode("\n", $lines);
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

        $maxAttempts = (int) config('services.openai.retry_attempts', 4);
        $maxAttempts = max(1, min(8, $maxAttempts));

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout((int) config('services.openai.timeout_seconds', 120))
                ->post('https://api.openai.com/v1/responses', $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && isset($data['error']) && $this->openAiPayloadErrorIsRetryable($data)) {
                    if ($attempt < $maxAttempts) {
                        $this->sleepOpenAiBackoff($attempt);
                        continue;
                    }
                }

                if (is_array($data) && isset($data['error'])) {
                    throw new \RuntimeException('OpenAI API error: ' . $response->body());
                }

                return is_array($data) ? $data : [];
            }

            $status = $response->status();
            $body = $response->body();
            $json = $response->json();
            $retryable = $status >= 500
                || $status === 429
                || (is_array($json) && $this->openAiPayloadErrorIsRetryable($json));

            if ($retryable && $attempt < $maxAttempts) {
                $this->sleepOpenAiBackoff($attempt);
                continue;
            }

            throw new \RuntimeException('OpenAI API error: ' . $body);
        }

        throw new \RuntimeException('OpenAI API error: request failed after '.$maxAttempts.' attempts.');
    }

    /**
     * OpenAI sometimes returns HTTP 5xx with error.type "server_error" (transient); retry with backoff.
     */
    protected function openAiPayloadErrorIsRetryable(array $payload): bool
    {
        $type = (string) ($payload['error']['type'] ?? '');

        return $type === 'server_error' || $type === 'rate_limit_exceeded' || $type === 'timeout';
    }

    protected function sleepOpenAiBackoff(int $attempt): void
    {
        $ms = (int) min(8000, 400 * (2 ** ($attempt - 1))) + random_int(0, 350);
        usleep($ms * 1000);
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

    /**
     * Текст для system prompt: WO и открытый CMM — только человекочитаемые номера; manual_id не передаём в реплики пользователю.
     */
    protected function buildAiPageContextLine(array $pageContext): string
    {
        $lines = [];

        $currentWo = $pageContext['current_workorder'] ?? null;
        if (is_array($currentWo) && ! empty($currentWo['id'])) {
            $woNo = (int) ($currentWo['number'] ?? 0);
            $manualId = (int) ($currentWo['manual_id'] ?? 0);
            $cmmNumber = '';
            $cmmTitle = '';
            if ($manualId > 0) {
                $manual = Manual::query()->find($manualId);
                if ($manual) {
                    $cmmNumber = trim((string) ($manual->number ?? ''));
                    $cmmTitle = trim((string) ($manual->title ?? ''));
                }
            }
            if ($cmmNumber !== '') {
                $lines[] = 'Workorder context: WO '.$woNo.', linked CMM number «'.$cmmNumber.'»'
                    .($cmmTitle !== '' ? ', title «'.$cmmTitle.'»' : '')
                    .'. When speaking to the user, use this CMM number for the manual — never manual_id or internal ids.';
            } else {
                $lines[] = 'Workorder context: WO '.$woNo.'. When speaking to the user, use only this WO number — never internal database ids.';
            }
        }

        $cm = $pageContext['current_manual'] ?? null;
        if (is_array($cm)) {
            $mn = trim((string) ($cm['number'] ?? ''));
            $mt = trim((string) ($cm['title'] ?? ''));
            if ($mn !== '' || $mt !== '') {
                $lines[] = 'Browser: user is viewing the CMM manual page for «'.$mn.'»'
                    .($mt !== '' ? ' — '.$mt : '')
                    .'. Refer to this manual only by CMM number «'.$mn.'» — never manual_id or internal ids.';
            }
        }

        return $lines === [] ? '' : implode("\n", $lines);
    }

    /**
     * Подстраивает язык ответа под последнее пользовательское сообщение (RU/EN).
     */
    protected function buildReplyLanguageInstruction(string $userMessage): string
    {
        $t = trim($userMessage);
        if ($t === '') {
            return 'Language for this reply: default to **English**.';
        }

        $cyr = 0;
        $lat = 0;
        if (preg_match_all('/\p{Cyrillic}/u', $t, $m)) {
            $cyr = count($m[0]);
        }
        if (preg_match_all('/\p{Latin}/u', $t, $m)) {
            $lat = count($m[0]);
        }

        if ($cyr >= 2 && $cyr >= $lat) {
            return 'Language for this reply: the user wrote in **Russian** — answer **fully in Russian** (same terms, tone, and UI hints).';
        }
        if ($lat >= 2 && $lat > $cyr) {
            return 'Language for this reply: the user wrote in **English** — answer **fully in English**.';
        }
        if ($cyr >= 1 && $cyr >= $lat) {
            return 'Language for this reply: the user message is **Russian** (or mixed with dominant Cyrillic) — answer **fully in Russian**.';
        }

        return 'Language for this reply: default to **English** (short or ambiguous input). Use another language only when the latest user message is clearly in that language.';
    }

    protected function assistantDisplayName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || preg_match('/\p{Cyrillic}/u', $name)) {
            return 'Avi';
        }

        $name = preg_replace('/[^A-Za-z0-9 ._-]/', '', $name) ?? '';
        $name = trim($name);

        return $name !== '' ? $name : 'Avi';
    }

    protected function systemPrompt(User $user, array $pageContext = [], string $latestUserMessage = ''): string
    {
        $contextLine = $this->buildAiPageContextLine($pageContext);

        $pageRoute = $pageContext['page']['route'] ?? null;
        if (is_string($pageRoute) && $pageRoute !== '') {
            $contextLine .= ($contextLine !== '' ? "\n" : '')
                . "Current browser screen (Laravel route name): «{$pageRoute}». Use this with the UI NAVIGATION MAP to tailor steps (e.g. user already on mains.show vs workorders.index).";
        }

        $origin = $pageContext['origin'] ?? null;
        if (is_string($origin) && $origin !== '' && preg_match('#^https?://#i', $origin)) {
            $contextLine .= ($contextLine !== '' ? "\n" : '')
                . "Browser origin for building links: {$origin}. Workorder list with search box pre-filled: {$origin}/workorders?q=<url-encoded terms> (use a markdown link; the chat UI shows only clickable text, not the raw URL).";
        }

        $uiNavigationBlock = $this->buildUiNavigationHelpBlock();

        $fullName = trim((string)($user->name ?? ''));
        $firstName = $this->userFirstName($user);
        $addressHint = $fullName !== ''
            ? "The logged-in user's name is «{$fullName}». Address them by first name «{$firstName}» in every reply (use it naturally: greeting, transitions, closing — not in every single sentence)."
            : 'The user has no name on file; address them neutrally (e.g. «коллега» / «colleague»).';

        $agentName = $this->assistantDisplayName((string) config('services.openai.agent_name', 'Assistant'));

        $languageInstruction = $this->buildReplyLanguageInstruction($latestUserMessage);
        $roleCapabilities = $this->capabilityPromptBlock($user);
        $roleSpecificBehavior = $this->roleSpecificBehaviorPrompt($user);

        return <<<PROMPT
You are «{$agentName}», the AI assistant for an aviation maintenance workshop (workorders, tasks, manuals, photos, damage notes). Do not mention Laravel, PHP, frameworks, or programming stack to the user unless they explicitly ask about IT internals.
Introduce yourself by this name when appropriate (first greeting, if asked who you are, or when it fits naturally). Do not use a different name or title.

Assistant name (identity — important):
- Always spell your name exactly as «{$agentName}» using **Latin letters only** (readable for everyone). Do **not** use Cyrillic for your name (e.g. «Ави», «Авиоша»), even when the rest of the reply is in Russian — e.g. say «Я — {$agentName}» or «Я, {$agentName}, …».

Personalization (mandatory):
{$addressHint}
- {$languageInstruction}
- If the user switches language mid-thread, follow the **latest** user message language.
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
{$roleCapabilities}

Do NOT claim you can: upload or delete files or photos, send email or notifications, edit workorders/tasks in bulk, change statuses or approve by yourself, export PDF/Excel, run reports, replace procedures, access data you cannot fetch with tools, or any other feature not listed above.

Communication style (strict):
- Never mention or write internal workorder database IDs to the user — only WO number (номер воркордера) and human-readable facts.
- For CMM manuals, always use the **manual number** (номер CMM, e.g. 32-21-09) and title if helpful — **never** say `manual_id`, «manual id 12», internal row ids, or other database keys for manuals.
- Speak only in plain human language (Russian or English, matching the user). No SQL, no programming code, no database queries, no framework names, no technical dumps.
- Do not show raw JSON, stack traces, or API payloads to the user; summarize what they mean in words.
- For workorder lists from tools: use markdown links only on the WO number, e.g. `[WO 107300](url)` then plain text after ` — ` for the rest of the line. Do not wrap the whole line in a link; do not paste bare URLs (the widget renders links as clickable text without showing the address).
- If the user asks how something works technically, explain the idea in simple terms without code. If they explicitly ask for code/SQL, briefly refuse in a friendly way and describe what to do in the UI or in general terms.

Important behavior:
- If the question is general, answer directly without tools.
- If the question needs real data from the system, use tools.
{$roleSpecificBehavior}
- If the user asks about number of pictures/photos/images on workorders, use countWorkorderImages. For "sum/total across all workorders", call it with `mode: "total"` and answer with the total image count plus how many workorders have images. For "top 10 with most pictures", call it with limit 10 and format each result as `[WO <number>](url) — <N> images`.
- If the user asks to find a part/unit by serial number, S/N, SN, or asks which workorder a serial belongs to, use lookupSerialNumber. Format matches as `[WO <number>](url) — <source>, <part name>, P/N <part_number>, IPL <ipl_num>, S/N <serial>` when fields exist. If there are multiple matches, list them briefly. If no match is found, say no matching serial number was found.
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
