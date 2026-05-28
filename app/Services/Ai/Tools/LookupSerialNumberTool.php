<?php

namespace App\Services\Ai\Tools;

use App\Models\Component;
use App\Models\ExtraProcess;
use App\Models\LogCard;
use App\Models\Paint;
use App\Models\Tdr;
use App\Models\User;
use App\Models\Workorder;
use App\Models\WorkorderUnitInspection;
use Illuminate\Database\Eloquent\Builder;

class LookupSerialNumberTool
{
    public function run(User $user, array $args): array
    {
        $serial = $this->normalizeSerial((string)($args['serial_number'] ?? ''));
        $limit = max(1, min(50, (int)($args['limit'] ?? 20)));

        if ($serial === '') {
            return ['ok' => false, 'message' => 'Provide a serial number.'];
        }

        $matches = collect()
            ->merge($this->workorderMatches($user, $serial, $limit))
            ->merge($this->tdrMatches($user, $serial, $limit))
            ->merge($this->unitInspectionMatches($user, $serial, $limit))
            ->merge($this->logCardMatches($user, $serial, $limit))
            ->merge($this->extraProcessMatches($user, $serial, $limit))
            ->merge($this->paintMatches($serial, $limit))
            ->sortBy(fn (array $match) => ($match['match_type'] ?? null) === 'exact' ? 0 : 1)
            ->take($limit)
            ->values();

        return [
            'ok' => true,
            'serial_number' => $serial,
            'count' => $matches->count(),
            'matches' => $matches->all(),
        ];
    }

    public function schema(): array
    {
        return [
            'type' => 'function',
            'name' => 'lookupSerialNumber',
            'description' => 'Find a part/unit by full or partial serial number across the app and report which workorder it belongs to. Search workorders, TDR component inspections, unit inspections, Log Card received/dispatched rows, extra processes, and paint/lost records. Do not expose internal ids.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'serial_number' => [
                        'type' => 'string',
                        'description' => 'Full or partial serial number / S/N as entered by the user.',
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Maximum number of matches to return.',
                    ],
                ],
                'required' => ['serial_number'],
                'additionalProperties' => false,
            ],
        ];
    }

    private function workorderMatches(User $user, string $serial, int $limit): array
    {
        return Workorder::withDrafts()
            ->with(['customer:id,name', 'unit:id,name,part_number,manual_id'])
            ->where($this->serialWhere('serial_number', $serial))
            ->orderByRaw($this->exactFirstSql('serial_number'), [$this->normalizeForCompare($serial)])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->filter(fn (Workorder $workorder) => $user->can('workorders.view', $workorder))
            ->map(function (Workorder $workorder) use ($serial) {
                return [
                    'source' => 'workorder unit serial',
                    'workorder_number' => $workorder->number,
                    'serial_field' => 'workorder serial_number',
                    'serial_number' => $workorder->serial_number,
                    'match_type' => $this->serialMatchType($workorder->serial_number, $serial),
                    'part_name' => $workorder->unit?->name,
                    'part_number' => $workorder->unit?->part_number,
                    'ipl_num' => null,
                    'customer' => $workorder->customer?->name,
                    'open_url' => route('mains.show', $workorder->id),
                ];
            })
            ->values()
            ->all();
    }

    private function tdrMatches(User $user, string $serial, int $limit): array
    {
        return Tdr::query()
            ->with([
                'workorder.customer:id,name',
                'component:id,name,part_number,ipl_num',
                'orderComponent:id,name,part_number,ipl_num',
            ])
            ->where(function (Builder $query) use ($serial): void {
                $query->where($this->serialWhere('serial_number', $serial))
                    ->orWhere($this->serialWhere('assy_serial_number', $serial));
            })
            ->orderByRaw($this->exactFirstSql('serial_number', 'assy_serial_number'), [
                $this->normalizeForCompare($serial),
                $this->normalizeForCompare($serial),
            ])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->filter(fn (Tdr $tdr) => $tdr->workorder && $user->can('workorders.view', $tdr->workorder))
            ->map(function (Tdr $tdr) use ($serial) {
                $component = $tdr->orderComponent ?? $tdr->component;
                [$matchedField, $matchedSerial] = $this->matchedSerialField($serial, [
                    'tdr serial_number' => $tdr->serial_number,
                    'tdr assy_serial_number' => $tdr->assy_serial_number,
                ]);

                return [
                    'source' => 'TDR component/part row',
                    'workorder_number' => $tdr->workorder?->number,
                    'serial_field' => $matchedField,
                    'serial_number' => $matchedSerial,
                    'match_type' => $this->serialMatchType($matchedSerial, $serial),
                    'part_name' => $component?->name,
                    'part_number' => $component?->part_number,
                    'ipl_num' => $component?->ipl_num,
                    'customer' => $tdr->workorder?->customer?->name,
                    'open_url' => $tdr->workorder ? route('mains.show', $tdr->workorder->id) : null,
                ];
            })
            ->values()
            ->all();
    }

    private function unitInspectionMatches(User $user, string $serial, int $limit): array
    {
        return WorkorderUnitInspection::query()
            ->with(['workorder.customer:id,name', 'condition:id,name'])
            ->where(function (Builder $query) use ($serial): void {
                $query->where($this->serialWhere('serial_number', $serial))
                    ->orWhere($this->serialWhere('assy_serial_number', $serial));
            })
            ->orderByRaw($this->exactFirstSql('serial_number', 'assy_serial_number'), [
                $this->normalizeForCompare($serial),
                $this->normalizeForCompare($serial),
            ])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->filter(fn (WorkorderUnitInspection $row) => $row->workorder && $user->can('workorders.view', $row->workorder))
            ->map(function (WorkorderUnitInspection $row) use ($serial) {
                [$matchedField, $matchedSerial] = $this->matchedSerialField($serial, [
                    'unit inspection serial_number' => $row->serial_number,
                    'unit inspection assy_serial_number' => $row->assy_serial_number,
                ]);

                return [
                    'source' => 'unit inspection',
                    'workorder_number' => $row->workorder?->number,
                    'serial_field' => $matchedField,
                    'serial_number' => $matchedSerial,
                    'match_type' => $this->serialMatchType($matchedSerial, $serial),
                    'part_name' => $row->condition?->name,
                    'part_number' => null,
                    'ipl_num' => null,
                    'customer' => $row->workorder?->customer?->name,
                    'open_url' => $row->workorder ? route('mains.show', $row->workorder->id) : null,
                ];
            })
            ->values()
            ->all();
    }

    private function extraProcessMatches(User $user, string $serial, int $limit): array
    {
        return ExtraProcess::query()
            ->with(['workorder.customer:id,name', 'component:id,name,part_number,ipl_num'])
            ->where($this->serialWhere('serial_num', $serial))
            ->orderByRaw($this->exactFirstSql('serial_num'), [$this->normalizeForCompare($serial)])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->filter(fn (ExtraProcess $row) => $row->workorder && $user->can('workorders.view', $row->workorder))
            ->map(fn (ExtraProcess $row) => [
                'source' => 'extra process part',
                'workorder_number' => $row->workorder?->number,
                'serial_field' => 'extra_processes serial_num',
                'serial_number' => $row->serial_num,
                'match_type' => $this->serialMatchType($row->serial_num, $serial),
                'part_name' => $row->component?->name,
                'part_number' => $row->component?->part_number,
                'ipl_num' => $row->component?->ipl_num,
                'customer' => $row->workorder?->customer?->name,
                'open_url' => $row->workorder ? route('mains.show', $row->workorder->id) : null,
            ])
            ->values()
            ->all();
    }

    private function logCardMatches(User $user, string $serial, int $limit): array
    {
        $like = '%'.$this->escapeLike($serial).'%';

        $logCards = LogCard::query()
            ->with(['workorder.customer:id,name'])
            ->where(function (Builder $query) use ($like): void {
                $query->where('component_data', 'like', $like)
                    ->orWhere('component_data_out', 'like', $like);
            })
            ->latest('id')
            ->limit($limit)
            ->get()
            ->filter(fn (LogCard $logCard) => $logCard->workorder && $user->can('workorders.view', $logCard->workorder));

        $componentIds = $logCards
            ->flatMap(fn (LogCard $logCard) => collect([
                $this->decodeRows($logCard->component_data),
                $this->decodeRows($logCard->component_data_out),
            ])->flatten(1))
            ->pluck('component_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $components = $componentIds->isEmpty()
            ? collect()
            : Component::query()
                ->whereIn('id', $componentIds)
                ->get(['id', 'name', 'part_number', 'ipl_num'])
                ->keyBy('id');

        return $logCards
            ->flatMap(function (LogCard $logCard) use ($serial, $components) {
                $matches = [];

                foreach ([
                    'component_data' => 'Log Card as received row',
                    'component_data_out' => 'Log Card as dispatched row',
                ] as $field => $source) {
                    foreach ($this->decodeRows($logCard->{$field}) as $row) {
                        if (! is_array($row)) {
                            continue;
                        }

                        [$matchedField, $matchedSerial] = $this->matchedSerialField($serial, [
                            $field.' serial_number' => $row['serial_number'] ?? null,
                            $field.' assy_serial_number' => $row['assy_serial_number'] ?? null,
                            $field.' serial' => $row['serial'] ?? null,
                        ]);

                        if ($this->serialMatchType($matchedSerial, $serial) === 'unknown') {
                            continue;
                        }

                        $component = isset($row['component_id'])
                            ? $components->get((int) $row['component_id'])
                            : null;

                        $matches[] = [
                            'source' => $source,
                            'workorder_number' => $logCard->workorder?->number,
                            'serial_field' => $matchedField,
                            'serial_number' => $matchedSerial,
                            'match_type' => $this->serialMatchType($matchedSerial, $serial),
                            'part_name' => $row['name'] ?? $row['description'] ?? $component?->name,
                            'part_number' => $row['part_number'] ?? $component?->part_number,
                            'ipl_num' => $row['ipl_num'] ?? $component?->ipl_num,
                            'customer' => $logCard->workorder?->customer?->name,
                            'open_url' => $logCard->workorder ? route('mains.show', $logCard->workorder->id) : null,
                        ];
                    }
                }

                return $matches;
            })
            ->take($limit)
            ->values()
            ->all();
    }

    private function paintMatches(string $serial, int $limit): array
    {
        return Paint::query()
            ->where($this->serialWhere('serial_number', $serial))
            ->orderByRaw($this->exactFirstSql('serial_number'), [$this->normalizeForCompare($serial)])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Paint $paint) => [
                'source' => 'paint/lost part record',
                'workorder_number' => null,
                'serial_field' => 'paint serial_number',
                'serial_number' => $paint->serial_number,
                'match_type' => $this->serialMatchType($paint->serial_number, $serial),
                'part_name' => $paint->comment,
                'part_number' => $paint->part_number,
                'ipl_num' => null,
                'customer' => null,
                'open_url' => null,
                'note' => 'This source has no direct workorder link.',
            ])
            ->values()
            ->all();
    }

    private function serialWhere(string $column, string $serial): callable
    {
        return fn (Builder $query) => $query->whereRaw(
            'LOWER(TRIM('.$column.')) LIKE ?',
            ['%'.$this->escapeLike($this->normalizeForCompare($serial)).'%']
        );
    }

    private function exactFirstSql(string ...$columns): string
    {
        $checks = collect($columns)
            ->map(fn (string $column) => 'LOWER(TRIM('.$column.')) = ?')
            ->implode(' OR ');

        return 'CASE WHEN '.$checks.' THEN 0 ELSE 1 END';
    }

    private function matchedSerialField(string $needle, array $fields): array
    {
        foreach ($fields as $field => $value) {
            if ($this->serialMatchType($value, $needle) === 'exact') {
                return [$field, $value];
            }
        }

        foreach ($fields as $field => $value) {
            if ($this->serialMatchType($value, $needle) === 'partial') {
                return [$field, $value];
            }
        }

        $field = array_key_first($fields);

        return [$field, $fields[$field] ?? null];
    }

    private function decodeRows(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    private function serialMatchType(mixed $value, string $needle): string
    {
        $haystack = $this->normalizeForCompare((string) $value);
        $needle = $this->normalizeForCompare($needle);

        if ($haystack !== '' && $haystack === $needle) {
            return 'exact';
        }

        return $haystack !== '' && $needle !== '' && str_contains($haystack, $needle)
            ? 'partial'
            : 'unknown';
    }

    private function normalizeForCompare(string $serial): string
    {
        return mb_strtolower($this->normalizeSerial($serial));
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function normalizeSerial(string $serial): string
    {
        return trim(preg_replace('/\s+/', ' ', $serial) ?? '');
    }
}
