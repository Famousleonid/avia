<?php

namespace App\Services;

use App\Models\StdProcess;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StdProcessAuditService
{
    public function conflicts(?int $manualId = null, ?string $std = null): Collection
    {
        $query = StdProcess::query()
            ->with([
                'manual:id,number,title',
                'component:id,manual_id,ipl_num,part_number,name',
            ])
            ->whereNotNull('component_id');

        if ($manualId !== null && $manualId > 0) {
            $query->where('manual_id', $manualId);
        }

        if ($std !== null && in_array($std, StdProcess::validStdValues(), true)) {
            $query->where('std', $std);
        }

        $rows = $query
            ->orderBy('manual_id')
            ->orderBy('std')
            ->orderBy('id')
            ->get()
            ->map(function (StdProcess $row): ?array {
                $component = $row->component;
                if (! $component) {
                    return null;
                }

                $baseIpl = self::numericBaseIpl($component->ipl_num);
                if ($baseIpl === null) {
                    return null;
                }

                return [
                    'std_process_id' => (int) $row->id,
                    'manual_id' => (int) $row->manual_id,
                    'manual_number' => (string) ($row->manual?->number ?? ''),
                    'manual_title' => (string) ($row->manual?->title ?? ''),
                    'std' => (string) $row->std,
                    'base_ipl' => $baseIpl,
                    'ipl_num' => (string) ($component->ipl_num ?? ''),
                    'part_number' => (string) ($component->part_number ?? ''),
                    'description' => (string) ($component->name ?? ''),
                    'process' => trim((string) $row->process),
                    'process_key' => self::processKey($row->process),
                    'qty' => (int) $row->qty,
                ];
            })
            ->filter()
            ->values();

        return $rows
            ->groupBy(fn (array $row): string => implode('|', [
                $row['manual_id'],
                $row['std'],
                $row['base_ipl'],
            ]))
            ->map(function (Collection $group): ?array {
                $processes = $group
                    ->mapWithKeys(fn (array $row): array => [$row['process_key'] => $row['process']])
                    ->filter(fn (string $value): bool => $value !== '')
                    ->all();

                if (count($processes) < 2) {
                    return null;
                }

                $first = $group->first();

                return [
                    'manual_id' => $first['manual_id'],
                    'manual_number' => $first['manual_number'],
                    'manual_title' => $first['manual_title'],
                    'std' => $first['std'],
                    'base_ipl' => $first['base_ipl'],
                    'processes' => array_values($processes),
                    'rows' => $group->values()->all(),
                ];
            })
            ->filter()
            ->sortBy([
                ['manual_number', 'asc'],
                ['std', 'asc'],
                ['base_ipl', 'asc'],
            ])
            ->values();
    }

    public function warningsByStdProcessIdForManual(int $manualId): array
    {
        $warnings = [];

        foreach ($this->conflicts($manualId) as $conflict) {
            $rows = collect($conflict['rows']);
            $summary = $rows
                ->map(fn (array $row): string => trim($row['ipl_num'].' => '.$row['process']))
                ->implode('; ');

            $message = sprintf(
                'Same numeric IPL group %s has mixed process values: %s. Rows: %s',
                $conflict['base_ipl'],
                implode(', ', $conflict['processes']),
                $summary
            );

            foreach ($conflict['rows'] as $row) {
                $warnings[(int) $row['std_process_id']] = [
                    'base_ipl' => $conflict['base_ipl'],
                    'processes' => $conflict['processes'],
                    'message' => $message,
                ];
            }
        }

        return $warnings;
    }

    public static function numericBaseIpl(?string $ipl): ?string
    {
        $value = trim((string) $ipl);
        if ($value === '') {
            return null;
        }

        $lines = preg_split('/\R+/', $value) ?: [];
        $value = strtoupper(trim((string) ($lines[0] ?? $value)));
        $value = preg_replace('/\s+/', '', $value) ?? $value;

        if (! preg_match('/^(\d+)[A-Z]*-(\d+)/', $value, $matches)) {
            return null;
        }

        return ((string) ((int) $matches[1])).'-'.((string) ((int) $matches[2]));
    }

    public static function processKey(?string $process): string
    {
        $value = Str::of((string) $process)->squish()->toString();
        if ($value === '') {
            return '';
        }

        $tokens = preg_split('/[\/,;]+/', $value) ?: [];
        $tokens = array_values(array_filter(
            array_map(static fn (string $token): string => trim($token), $tokens),
            static fn (string $token): bool => $token !== ''
        ));

        if (count($tokens) > 1 && collect($tokens)->every(fn (string $token): bool => ctype_digit($token))) {
            $tokens = array_values(array_unique(array_map('intval', $tokens)));
            sort($tokens, SORT_NUMERIC);

            return implode('/', array_map('strval', $tokens));
        }

        return Str::upper($value);
    }
}
