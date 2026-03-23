<?php

namespace App\Services\Ai\Tools;

use App\Models\User;
use App\Models\Workorder;
use Illuminate\Support\Facades\Schema;

class SearchWorkordersTool
{
    /**
     * Searchable workorder columns (partial match). Related tables are not searched here.
     */
    private const SEARCHABLE_COLUMNS = [
        'number', 'user_id', 'unit_id', 'instruction_id', 'open_at', 'customer_id',
        'approve', 'approve_at', 'description', 'manual', 'serial_number', 'customer_po', 'modified', 'is_draft',
    ];

    /**
     * Search workorders by substring on allowed workorder fields only.
     * Returns links to mains.show for the AI to present as clickable list (do not expose internal DB id to users).
     */
    public function run(User $user, array $args): array
    {
        $search = trim((string)($args['query'] ?? ''));
        if ($search === '') {
            return [
                'ok' => false,
                'message' => 'Empty query.',
            ];
        }

        $limit = (int)($args['limit'] ?? 25);
        $limit = max(1, min(50, $limit));

        $like = '%'.$this->escapeLike($search).'%';

        $tableColumns = Schema::getColumnListing('workorders');
        $columns = array_values(array_intersect(self::SEARCHABLE_COLUMNS, $tableColumns));

        // withDrafts() already returns a Builder; do not chain ->query() on it.
        $q = Workorder::withDrafts();

        $q->where(function ($outer) use ($columns, $like) {
            foreach ($columns as $col) {
                $outer->orWhere('workorders.'.$col, 'like', $like);
            }
        });

        $candidates = $q->with(['customer', 'unit'])
            ->latest('id')
            ->limit(400)
            ->get();

        $visible = $candidates->filter(fn ($wo) => $user->can('workorders.view', $wo))->take($limit);

        $workorders = $visible->map(function ($wo) {
            $label = 'WO '.$wo->number;
            $customer = $wo->customer?->name;
            $unit = $wo->unit?->name ?: $wo->unit?->part_number;
            if ($customer) {
                $label .= ' — '.$customer;
            }
            if ($unit) {
                $label .= ' — '.$unit;
            }

            return [
                'number' => $wo->number,
                'label' => $label,
                'url' => route('mains.show', $wo->id),
            ];
        })->values()->all();

        return [
            'ok' => true,
            'count' => count($workorders),
            'workorders' => $workorders,
            'instruction_for_model' => 'Present each row as a markdown link [label](url). Never mention internal database IDs — only WO number in text. If count is 0, say nothing was found.',
        ];
    }

    public function schema(): array
    {
        return [
            'type' => 'function',
            'name' => 'searchWorkorders',
            'description' => 'Find workorders by partial match on workorder fields only: number, user_id, unit_id, instruction_id, open_at, customer_id, approve, approve_at, description, manual, serial_number, customer_po, modified, is_draft. Returns links to open the main page; never expose internal row id to the user.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => [
                        'type' => 'string',
                        'description' => 'Substring to search for (e.g. serial, PO, customer fragment, place, description, WO number part).',
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Max results after permission filter (default 25, max 50).',
                    ],
                ],
                'required' => ['query'],
                'additionalProperties' => false,
            ],
        ];
    }

    private function escapeLike(string $s): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $s);
    }
}
