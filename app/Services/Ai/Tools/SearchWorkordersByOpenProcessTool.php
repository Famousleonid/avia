<?php

namespace App\Services\Ai\Tools;

use App\Models\TdrProcess;
use App\Models\User;

class SearchWorkordersByOpenProcessTool
{
    /**
     * Find workorders (all visible to current user) that have open process rows:
     * date_start is set, date_finish is null.
     * Optional filters: process name fragment and customer name fragment.
     * Returns one row per workorder with markdown-ready URL.
     */
    public function run(User $user, array $args): array
    {
        $processQuery = trim((string)($args['process_query'] ?? ''));
        $customerQuery = trim((string)($args['customer_query'] ?? ''));

        $limit = (int)($args['limit'] ?? 25);
        $limit = max(1, min(50, $limit));

        $q = TdrProcess::query()
            ->with([
                'processName:id,name',
                'tdr:id,workorder_id,component_id',
                'tdr.workorder:id,number,customer_id',
                'tdr.workorder.customer:id,name',
                'tdr.component:id,name,part_number',
            ])
            ->whereNotNull('date_start')
            ->whereNull('date_finish')
            ->where(function ($w) {
                $w->whereNull('ignore_row')->orWhere('ignore_row', false);
            });

        if ($processQuery !== '') {
            $like = '%' . $this->escapeLike($processQuery) . '%';
            $q->whereHas('processName', function ($pq) use ($like) {
                $pq->where('name', 'like', $like);
            });
        }

        if ($customerQuery !== '') {
            $like = '%' . $this->escapeLike($customerQuery) . '%';
            $q->whereHas('tdr.workorder.customer', function ($cq) use ($like) {
                $cq->where('name', 'like', $like);
            });
        }

        $rows = $q->latest('id')
            ->limit(1200)
            ->get();

        $visible = $rows->filter(function (TdrProcess $row) use ($user) {
            $wo = $row->tdr?->workorder;
            return $wo && $user->can('workorders.view', $wo);
        });

        // One result per workorder to match user phrasing "show all WO..."
        $uniqueByWo = $visible
            ->groupBy(fn (TdrProcess $row) => (int)($row->tdr?->workorder?->id ?? 0))
            ->filter(fn ($group, $woId) => $woId > 0)
            ->map(function ($group) {
                /** @var TdrProcess $first */
                $first = $group->first();
                $wo = $first->tdr?->workorder;
                $customer = $wo?->customer?->name ?? '';
                $processName = (string)($first->processName?->name ?? '');
                $component = $first->tdr?->component;

                $componentLabel = trim((string)($component?->name ?? ''));
                $partNumber = trim((string)($component?->part_number ?? ''));
                $detail = $componentLabel !== '' ? $componentLabel : 'Component';
                if ($partNumber !== '') {
                    $detail .= ' (PN ' . $partNumber . ')';
                }

                $label = 'WO ' . (int)$wo->number;
                if ($customer !== '') {
                    $label .= ' — ' . $customer;
                }
                if ($processName !== '') {
                    $label .= ' — ' . $processName . ' (open)';
                }
                $label .= ' — ' . $detail;

                return [
                    'number' => (int)$wo->number,
                    'customer' => $customer,
                    'process' => $processName,
                    'date_start' => optional($first->date_start)->format('Y-m-d'),
                    'detail' => $detail,
                    'label' => $label,
                    'url' => route('mains.show', $wo->id),
                ];
            })
            ->values()
            ->take($limit)
            ->all();

        return [
            'ok' => true,
            'count' => count($uniqueByWo),
            'filter' => [
                'customer_query' => $customerQuery !== '' ? $customerQuery : null,
                'process_query' => $processQuery !== '' ? $processQuery : null,
                'condition' => 'date_start IS NOT NULL AND date_finish IS NULL AND ignore_row = 0',
                'scope' => 'all visible workorders',
            ],
            'workorders' => $uniqueByWo,
            'instruction_for_model' => 'This is an intersection filter (customer + open process). Return one line per result as: [WO <number>](url) — customer — process (open) — detail. Do not mention internal ids and do not output bare URLs.',
        ];
    }

    public function schema(): array
    {
        return [
            'type' => 'function',
            'name' => 'searchWorkordersByOpenProcess',
            'description' => 'Find all visible workorders that have open process rows (tdr_process date_start set, date_finish empty, ignore_row is false). Supports optional customer and process name filters and returns links to open workorder main page.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'customer_query' => [
                        'type' => 'string',
                        'description' => 'Optional customer name fragment (e.g. "Liebherr").',
                    ],
                    'process_query' => [
                        'type' => 'string',
                        'description' => 'Optional process name fragment (e.g. "Machining").',
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Max workorders after permission filter (default 25, max 50).',
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

