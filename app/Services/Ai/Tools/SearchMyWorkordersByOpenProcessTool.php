<?php

namespace App\Services\Ai\Tools;

use App\Models\TdrProcess;
use App\Models\User;

class SearchMyWorkordersByOpenProcessTool
{
    /**
     * Find current user's workorders that have started but not finished TDR processes.
     */
    public function run(User $user, array $args): array
    {
        $processQuery = trim((string)($args['process_query'] ?? ''));
        $limit = (int)($args['limit'] ?? 25);
        $limit = max(1, min(50, $limit));

        $q = TdrProcess::query()
            ->with([
                'processName:id,name',
                'tdr:id,workorder_id,component_id',
                'tdr.workorder:id,number,user_id',
                'tdr.component:id,name,part_number',
            ])
            ->whereNotNull('date_start')
            ->whereNull('date_finish')
            ->whereHas('tdr.workorder', function ($wq) use ($user) {
                $wq->where('user_id', $user->id);
            });

        if ($processQuery !== '') {
            $like = '%'.$this->escapeLike($processQuery).'%';
            $q->whereHas('processName', function ($pq) use ($like) {
                $pq->where('name', 'like', $like);
            });
        }

        $rows = $q->latest('id')
            ->limit(400)
            ->get();

        $filtered = $rows
            ->filter(function (TdrProcess $row) use ($user) {
                $wo = $row->tdr?->workorder;
                return $wo && $user->can('workorders.view', $wo);
            })
            ->take($limit);

        $items = $filtered->map(function (TdrProcess $row) {
            $wo = $row->tdr?->workorder;
            $component = $row->tdr?->component;
            $processName = (string)($row->processName?->name ?? '');

            $componentLabel = trim((string)($component?->name ?? ''));
            $partNumber = trim((string)($component?->part_number ?? ''));
            $detail = $componentLabel !== '' ? $componentLabel : 'Component';
            if ($partNumber !== '') {
                $detail .= ' (PN '.$partNumber.')';
            }

            $label = 'WO '.(int)$wo->number.' — '.$detail.' — '.$processName;

            return [
                'number' => (int)$wo->number,
                'process' => $processName,
                'date_start' => optional($row->date_start)->format('Y-m-d'),
                'detail' => $detail,
                'label' => $label,
                'url' => route('mains.show', $wo->id),
            ];
        })->values()->all();

        return [
            'ok' => true,
            'count' => count($items),
            'filter' => [
                'owner_user_id' => $user->id,
                'process_query' => $processQuery !== '' ? $processQuery : null,
                'condition' => 'date_start IS NOT NULL AND date_finish IS NULL',
            ],
            'workorders' => $items,
            'instruction_for_model' => 'One result per line. Only the WO number is a markdown link: [WO 107300](url) — plain text (process name, detail…). Do not wrap the entire line in a link; do not output bare URLs. Explain these are the current user\'s workorders only. Never mention internal database IDs.',
        ];
    }

    public function schema(): array
    {
        return [
            'type' => 'function',
            'name' => 'searchMyWorkordersByOpenProcess',
            'description' => 'Find only current user workorders (workorders.user_id = current user) that have started but unfinished TDR process rows (date_start exists, date_finish is null). Can filter by process name fragment like "machining". Returns links to workorder main page.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'process_query' => [
                        'type' => 'string',
                        'description' => 'Optional process name fragment (e.g. "machining", "ndt", "cad"). If omitted, returns all started-not-finished processes for my workorders.',
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Max results after permission filter (default 25, max 50).',
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
