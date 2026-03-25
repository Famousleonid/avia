<?php

namespace App\Services\Ai\Tools;

use App\Models\User;
use App\Models\Workorder;
use Illuminate\Support\Facades\Schema;

class SearchWorkordersTool
{
    /** Не ищем по surrogate key (и не подставляем имя колонки извне). */
    private const SKIP_WORKORDER_COLUMNS = ['id'];

    /**
     * Search workorders by substring on all workorder columns (except id) and related: customer, unit (+ manual), instruction, assigned user.
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

        // withDrafts() already returns a Builder; do not chain ->query() on it.
        $q = Workorder::withDrafts();

        $q->where(function ($outer) use ($tableColumns, $like) {
            foreach ($tableColumns as $col) {
                if (in_array($col, self::SKIP_WORKORDER_COLUMNS, true)) {
                    continue;
                }
                if (! preg_match('/^[a-zA-Z0-9_]+$/', (string) $col)) {
                    continue;
                }
                $outer->orWhere('workorders.'.$col, 'like', $like);
            }

            $outer->orWhereHas('customer', function ($cq) use ($like) {
                $cq->where('name', 'like', $like);
            });

            $outer->orWhereHas('unit', function ($uq) use ($like) {
                $uq->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('part_number', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('eff_code', 'like', $like);
                })->orWhereHas('manual', function ($mq) use ($like) {
                    $mq->where(function ($m) use ($like) {
                        $m->where('title', 'like', $like)
                            ->orWhere('number', 'like', $like)
                            ->orWhere('unit_name', 'like', $like);
                    });
                });
            });

            $outer->orWhereHas('instruction', function ($iq) use ($like) {
                $iq->where('name', 'like', $like);
            });

            $outer->orWhereHas('user', function ($uq) use ($like) {
                $uq->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            });
        });

        $candidates = $q->with(['customer', 'unit', 'instruction'])
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
            $instruction = $wo->instruction?->name;
            if ($instruction) {
                $label .= ' — '.$instruction;
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
            'instruction_for_model' => 'One result per line. Only the workorder number is a markdown link: [WO 107300](url) — then plain text description (customer, unit, etc.). Do not put the whole line inside the link; do not output bare URLs. Never mention internal database IDs. If count is 0, say nothing was found.',
        ];
    }

    public function schema(): array
    {
        return [
            'type' => 'function',
            'name' => 'searchWorkorders',
            'description' => 'Find workorders by partial match on all workorder table columns (except internal id), plus related: customer name, unit (name, part number, description, eff code) and linked manual (title, number, unit_name), instruction name, assigned user (name, email, phone). Returns links to open the main page; never expose internal row id to the user.',
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
