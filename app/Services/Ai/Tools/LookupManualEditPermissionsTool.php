<?php

namespace App\Services\Ai\Tools;

use App\Models\Manual;
use App\Models\User;

/**
 * Reads manual ↔ user edit permissions from manual_user_permissions (CMM manuals managed per user).
 */
class LookupManualEditPermissionsTool
{
    private const MAX_MANUALS = 100;

    private const MAX_ROWS = 250;

    public function run(User $user, array $args): array
    {
        $userName = trim((string)($args['user_name'] ?? ''));
        $manualNumber = trim((string)($args['manual_number'] ?? ''));
        $manualLib = trim((string)($args['manual_lib'] ?? ''));
        $onlyWithResponsibles = (bool)($args['only_with_responsibles'] ?? false);

        if ($userName === '' && $manualNumber === '' && $manualLib === '' && !$onlyWithResponsibles) {
            return [
                'ok' => false,
                'message' => 'Provide user_name and/or manual_number and/or manual_lib, or set only_with_responsibles=true.',
            ];
        }

        $manualLike = $manualNumber !== '' ? '%'.$this->escapeLike($manualNumber).'%' : null;
        $userLike = $userName !== '' ? '%'.$this->escapeLike($userName).'%' : null;
        $libLike = $manualLib !== '' ? '%'.$this->escapeLike($manualLib).'%' : null;

        $q = Manual::query()
            ->with(['permittedUsers' => function ($uq) use ($userLike) {
                if ($userLike !== null) {
                    $uq->where(function ($inner) use ($userLike) {
                        $inner->where('name', 'like', $userLike)
                            ->orWhere('email', 'like', $userLike);
                    });
                }
            }]);

        if ($manualLike !== null) {
            $q->where('number', 'like', $manualLike);
        }

        if ($libLike !== null) {
            $q->where('lib', 'like', $libLike);
        }

        if ($userLike !== null) {
            $q->whereHas('permittedUsers', function ($uq) use ($userLike) {
                $uq->where(function ($inner) use ($userLike) {
                    $inner->where('name', 'like', $userLike)
                        ->orWhere('email', 'like', $userLike);
                });
            });
        }

        if ($onlyWithResponsibles) {
            $q->whereHas('permittedUsers');
        }

        $manuals = $q->orderBy('number')->limit(self::MAX_MANUALS)->get();

        $entries = [];
        $manualsSummary = [];
        foreach ($manuals as $manual) {
            $responsibleNames = $manual->permittedUsers
                ->pluck('name')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($onlyWithResponsibles && empty($responsibleNames)) {
                continue;
            }

            $manualsSummary[] = [
                'manual_number' => $manual->number,
                'manual_lib' => $manual->lib,
                'manual_title' => $manual->title,
                'manual_url' => route('manuals.show', $manual->id),
                'responsible_names' => $responsibleNames,
            ];

            foreach ($manual->permittedUsers as $pu) {
                $entries[] = [
                    'user_name' => $pu->name,
                    'user_email' => $pu->email,
                    'manual_number' => $manual->number,
                    'manual_lib' => $manual->lib,
                    'manual_title' => $manual->title,
                    'manual_url' => route('manuals.show', $manual->id),
                ];
                if (count($entries) >= self::MAX_ROWS) {
                    break 2;
                }
            }
        }

        usort($entries, function ($a, $b) {
            $c = strcmp((string)$a['manual_number'], (string)$b['manual_number']);
            if ($c !== 0) {
                return $c;
            }

            return strcmp((string)$a['user_name'], (string)$b['user_name']);
        });

        usort($manualsSummary, function ($a, $b) {
            $c = strcmp((string)$a['manual_number'], (string)$b['manual_number']);
            if ($c !== 0) {
                return $c;
            }

            return strcmp((string)$a['manual_lib'], (string)$b['manual_lib']);
        });

        $truncated = $manuals->count() >= self::MAX_MANUALS || count($entries) >= self::MAX_ROWS;

        return [
            'ok' => true,
            'count' => count($entries),
            'manuals_count' => count($manualsSummary),
            'truncated' => $truncated,
            'manuals' => $manualsSummary,
            'entries' => $entries,
            'instruction_for_model' => 'Summarize in plain language. Permissions come only from manual_user_permissions (per-user CMM edit access). '
                .'For each manual use a markdown link on the manual number only, e.g. [32-21-09](manual_url). '
                .'Always show manual number and LIB together when available (e.g. 32-21-09, LIB 290). '
                .'If asked for all manuals with responsibles, use `manuals` and show responsible names per manual. '
                .'If asked manual number↔LIB mapping, answer from `manuals` (manual_number + manual_lib) even if entries are empty. '
                .'Never say manual_id or internal row ids to the user — only CMM numbers like 32-21-09. If count is 0, say no matching permissions were found. If truncated is true, say the list may be incomplete.',
        ];
    }

    public function schema(): array
    {
        return [
            'type' => 'function',
            'name' => 'lookupManualEditPermissions',
            'description' => 'Query manual access/responsibles using manual_user_permissions: which manuals a user may edit, who may edit a manual, list manuals that have responsibles, and map manual number ↔ LIB.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'user_name' => [
                        'type' => 'string',
                        'description' => 'Optional fragment of user display name or email (e.g. Eduard). Use to list manuals this user can edit.',
                    ],
                    'manual_number' => [
                        'type' => 'string',
                        'description' => 'Optional fragment of manual CMM number (e.g. 32-21-09). Use to list users who can edit that manual.',
                    ],
                    'manual_lib' => [
                        'type' => 'string',
                        'description' => 'Optional fragment of manual LIB value (e.g. 290). Use to find manual number(s) by LIB.',
                    ],
                    'only_with_responsibles' => [
                        'type' => 'boolean',
                        'description' => 'If true, return only manuals that have at least one responsible user in manual_user_permissions.',
                    ],
                ],
                'required' => [],
                'additionalProperties' => false,
            ],
        ];
    }

    private function escapeLike(string $s): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $s);
    }
}
