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
            ->when(is_numeric($query), function ($q) use ($query) {
                $q->orWhere('id', (int)$query);
            })
            ->orWhere('number', 'like', '%' . $query . '%')
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
                'id' => $workorder->id,
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
            'description' => 'Find workorder by id or number',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => [
                        'type' => 'string',
                        'description' => 'Workorder id or number',
                    ],
                ],
                'required' => ['query'],
                'additionalProperties' => false,
            ],
        ];
    }
}
