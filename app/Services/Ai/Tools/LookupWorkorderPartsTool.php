<?php

namespace App\Services\Ai\Tools;

use App\Models\Component;
use App\Models\User;
use App\Models\Workorder;

class LookupWorkorderPartsTool
{
    public function run(User $user, array $args): array
    {
        $workorderId = (int)($args['workorder_id'] ?? 0);
        $workorderNumber = trim((string)($args['workorder_number'] ?? ''));
        $manualId = (int)($args['manual_id'] ?? 0);
        $partNumber = trim((string)($args['part_number'] ?? ''));
        $iplNum = trim((string)($args['ipl_num'] ?? ''));
        $exact = (bool)($args['exact'] ?? false);
        $limit = max(1, min(50, (int)($args['limit'] ?? 20)));

        if ($workorderId <= 0 && $workorderNumber === '' && $manualId <= 0) {
            return ['ok' => false, 'message' => 'Provide workorder_id, workorder_number, or manual_id.'];
        }

        $manualInfo = null;
        if ($workorderId > 0 || $workorderNumber !== '') {
            $workorder = null;

            if ($workorderId > 0) {
                $workorder = Workorder::withDrafts()
                    ->with(['unit.manual'])
                    ->find($workorderId);
            }

            // fallback: sometimes model passes workorder NUMBER in workorder_id
            if (! $workorder && $workorderId > 0) {
                $workorder = Workorder::withDrafts()
                    ->with(['unit.manual'])
                    ->where('number', $workorderId)
                    ->first();
            }

            if (! $workorder && $workorderNumber !== '') {
                $workorder = Workorder::withDrafts()
                    ->with(['unit.manual'])
                    ->where('number', $workorderNumber)
                    ->first();
            }

            if (! $workorder) return ['ok' => false, 'message' => 'Workorder not found.'];
            if (! $user->can('workorders.view', $workorder)) {
                return ['ok' => false, 'message' => 'You do not have permission to view this workorder.'];
            }

            $manualId = (int)($workorder->unit?->manual_id ?? 0);
            if ($manualId <= 0) {
                return ['ok' => false, 'message' => 'No manual linked to this workorder/unit.'];
            }

            $manualInfo = [
                'workorder_id' => $workorder->id,
                'workorder_number' => $workorder->number,
                'manual_id' => $manualId,
                'manual_number' => $workorder->unit?->manual?->number,
                'manual_lib' => $workorder->unit?->manual?->lib,
            ];
        }

        $q = Component::query()->where('manual_id', $manualId);

        // If neither filter provided -> list all parts for manual (limited).
        if ($partNumber === '' && $iplNum === '') {
            // no-op filter
        } elseif ($partNumber !== '' && $iplNum !== '') {
            // explicit both -> AND
            $q->where('part_number', $exact ? '=' : 'like', $exact ? $partNumber : "%{$partNumber}%");
            $q->where('ipl_num', $exact ? '=' : 'like', $exact ? $iplNum : "%{$iplNum}%");
        } else {
            // one token provided -> search in BOTH fields (part OR ipl)
            $token = $partNumber !== '' ? $partNumber : $iplNum;
            $q->where(function ($w) use ($exact, $token) {
                if ($exact) {
                    $w->where('part_number', '=', $token)
                        ->orWhere('ipl_num', '=', $token);
                } else {
                    $w->where('part_number', 'like', "%{$token}%")
                        ->orWhere('ipl_num', 'like', "%{$token}%");
                }
            });
        }

        $rows = $q->orderBy('name')->limit($limit * 3)->get(['id', 'name', 'part_number', 'ipl_num', 'assy_part_number', 'assy_ipl_num']);

        $token = trim((string)($partNumber !== '' ? $partNumber : $iplNum));
        $rows = $rows->sortBy(function ($c) use ($token) {
            if ($token === '') return 100;
            $pn = (string)($c->part_number ?? '');
            $ipl = (string)($c->ipl_num ?? '');

            if (strcasecmp($pn, $token) === 0 || strcasecmp($ipl, $token) === 0) return 0; // exact first
            if (stripos($pn, $token) === 0 || stripos($ipl, $token) === 0) return 1; // prefix next
            if (stripos($pn, $token) !== false || stripos($ipl, $token) !== false) return 2; // contains
            return 10;
        })->take($limit)->values();

        return [
            'ok' => true,
            'context' => $manualInfo ?? ['manual_id' => $manualId],
            'query' => [
                'part_number' => $partNumber ?: null,
                'ipl_num' => $iplNum ?: null,
                'exact' => $exact,
                'limit' => $limit,
            ],
            'count' => $rows->count(),
            'items' => $rows->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'part_number' => $c->part_number,
                'ipl_num' => $c->ipl_num,
                'assy_part_number' => $c->assy_part_number,
                'assy_ipl_num' => $c->assy_ipl_num,
            ])->values()->all(),
        ];
    }

    public function schema(): array
    {
        return [
            'type' => 'function',
            'name' => 'lookupWorkorderParts',
            'description' => 'Resolve WO->manual->parts and lookup by part_number or ipl_num (both directions).',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'workorder_id' => ['type' => 'integer', 'description' => 'Workorder id (preferred)'],
                    'workorder_number' => ['type' => 'string', 'description' => 'Workorder number (fallback)'],
                    'manual_id' => ['type' => 'integer', 'description' => 'Manual id (if workorder is unknown)'],
                    'part_number' => ['type' => 'string', 'description' => 'Part number to find IPL'],
                    'ipl_num' => ['type' => 'string', 'description' => 'IPL number to find part'],
                    'exact' => ['type' => 'boolean'],
                    'limit' => ['type' => 'integer'],
                ],
                'additionalProperties' => false,
            ],
        ];
    }
}

