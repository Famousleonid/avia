<?php

namespace App\Services\Ai\Tools;

use App\Models\Workorder;
use App\Models\User;

class FindWorkorderTool
{
    public function run(User $user, array $args): array
    {
        $query = trim((string)($args['query'] ?? ''));

        if ($query === '') {
            return [
                'ok' => false,
                'message' => 'Empty query.',
            ];
        }

        $workorder = Workorder::withDrafts()
            ->where(function ($q) use ($query) {
                if (is_numeric($query)) {
                    $n = (int) $query;
                    $q->where('number', $n)
                        ->orWhere('number', 'like', '%'.$query.'%');
                } else {
                    $q->where('number', 'like', '%'.$query.'%');
                }
            })
            ->with(['customer', 'unit'])
            ->latest('id')
            ->first();

        if (! $workorder) {
            return [
                'ok' => false,
                'message' => 'Workorder not found.',
            ];
        }

        if (! $user->can('workorders.view', $workorder)) {
            return [
                'ok' => false,
                'message' => 'You do not have permission to view this workorder.',
            ];
        }

        return [
            'ok' => true,
            'workorder' => [
                'number' => $workorder->number,
                'status' => $workorder->status ?? null,
                'customer' => $workorder->customer->name ?? null,
                'unit' => $workorder->unit->name ?? null,
                'created_at' => optional($workorder->created_at)?->toDateTimeString(),
            ],
        ];
    }

    public function schema(): array
    {
        return [
            'type' => 'function',
            'name' => 'findWorkorder',
            'description' => 'Find one workorder by WO number (partial match). Do not expose internal database id to the user.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => [
                        'type' => 'string',
                        'description' => 'Workorder number (digits) or fragment',
                    ],
                ],
                'required' => ['query'],
                'additionalProperties' => false,
            ],
        ];
    }
}
