<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class QuantumUnitHistory
{
    public function path(): string { return storage_path('app/quantum/unit-history.json'); }

    public static function group(string $pn): string
    {
        $pn = strtoupper(trim($pn));
        $dash = strrpos($pn, '-');
        return $dash === false ? $pn : substr($pn, 0, $dash);
    }

    public function read(): array
    {
        return is_file($this->path())
            ? json_decode(file_get_contents($this->path()), true, 512, JSON_THROW_ON_ERROR)
            : ['exported_at' => null, 'rows' => []];
    }

    public function import(array $payload): void
    {
        Validator::make($payload, ['version' => 'required|in:1', 'exported_at' => 'required|date',
            'row_count' => 'required|integer|min:1|max:100000', 'rows' => 'required|array|min:1|max:100000'])->validate();
        if (count($payload['rows']) !== (int) $payload['row_count']) {
            throw ValidationException::withMessages(['rows' => 'Incomplete snapshot.']);
        }
        $ids = [];
        foreach ($payload['rows'] as &$row) {
            $row = Validator::make($row, [
                'source_id' => 'required|integer|min:1', 'wo_number' => 'required|string|max:100',
                'part_number' => 'present|nullable|string|max:255', 'customer' => 'present|nullable|string|max:255',
                'description' => 'sometimes|nullable|string|max:4000',
                'opened_at' => 'present|nullable|date_format:Y-m-d', 'closed_at' => 'present|nullable|date_format:Y-m-d',
                'quantity' => 'present|nullable|numeric|min:0', 'serials' => 'present|nullable|string|max:10000',
                'status' => 'present|nullable|string|max:100', 'quantity_review' => 'required|boolean',
                'parent_id' => 'present|nullable|integer|min:1',
            ])->validate();
            if (isset($ids[$row['source_id']])) { throw ValidationException::withMessages(['rows' => 'Duplicate Quantum WO identity.']); }
            $ids[$row['source_id']] = true;
            $row['group'] = self::group((string) $row['part_number']);
        }
        unset($row);
        File::ensureDirectoryExists(dirname($this->path()));
        $lock = fopen($this->path() . '.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) { throw new \RuntimeException('Cannot lock history snapshot'); }
        try {
            $previous = $this->read();
            $exported = CarbonImmutable::parse($payload['exported_at']);
            if ($exported->isAfter(CarbonImmutable::now()->addMinutes(5)) ||
                ($previous['exported_at'] && $exported->isBefore(CarbonImmutable::parse($previous['exported_at'])))) {
                throw ValidationException::withMessages(['exported_at' => 'Snapshot is older than current data or has a future timestamp.']);
            }
            File::replace($this->path(), json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }

    public function report(string $search, ?string $from = null, ?string $to = null): array
    {
        $snapshot = $this->read();
        $all = collect($snapshot['rows'])->filter(fn ($r) => !$r['parent_id']);
        $groups = $all->pluck('group')->filter()->unique()->sort()->values();
        $suggestionIndex = [];
        foreach ($all as $row) {
            $pn = strtoupper(trim((string) $row['part_number']));
            if ($pn === '') { continue; }
            $description = trim((string) ($row['description'] ?? ''));
            foreach (['group' => self::group($pn), 'part' => $pn] as $kind => $value) {
                if ($value === '') { continue; }
                $suggestionIndex[$value] ??= ['value' => $value, 'kinds' => [], 'descriptions' => []];
                $suggestionIndex[$value]['kinds'][$kind] = true;
                if ($description !== '') { $suggestionIndex[$value]['descriptions'][$description] = true; }
            }
        }
        ksort($suggestionIndex, SORT_NATURAL);
        $suggestions = array_values(array_map(static function ($item) {
            $kinds = $item['kinds'];
            $type = isset($kinds['group'], $kinds['part']) ? 'Group / P/N' : (isset($kinds['group']) ? 'Group' : 'P/N');
            $descriptions = array_keys($item['descriptions']);
            sort($descriptions, SORT_NATURAL | SORT_FLAG_CASE);
            return ['value' => $item['value'], 'label' => $type . ($descriptions ? ' · ' . implode(' / ', $descriptions) : '')];
        }, $suggestionIndex));
        $search = strtoupper(trim($search));
        // Keep every entered character: a full P/N must not expand to its siblings.
        $group = $search;
        $family = $search === '' ? collect() : $all->filter(
            fn ($r) => str_starts_with(strtoupper(trim((string) $r['part_number'])), $search)
        )->values();
        $rows = $family->filter(fn ($r) => (!$from || ($r['opened_at'] && $r['opened_at'] >= $from))
            && (!$to || ($r['opened_at'] && $r['opened_at'] <= $to)))
            ->sortByDesc(fn ($r) => ($r['opened_at'] ?? '') . ':' . str_pad($r['source_id'], 12, '0', STR_PAD_LEFT))->values();
        $dated = $rows->filter(fn ($r) => $r['opened_at']);
        $dates = $dated->pluck('opened_at')->unique()->sort()->values();
        $months = [];
        if ($dates->isNotEmpty()) {
            $start = CarbonImmutable::parse($from ?: $dates->first())->startOfMonth();
            $end = CarbonImmutable::parse($to ?: CarbonImmutable::today()->toDateString())->startOfMonth();
            $byMonth = $dated->groupBy(fn ($r) => substr($r['opened_at'], 0, 7));
            for ($month = $start; $month <= $end; $month = $month->addMonth()) {
                $items = $byMonth->get($month->format('Y-m'), collect());
                $months[] = ['month' => $month->format('M Y'), 'quantity' => $items->sum('quantity'), 'workorders' => $items->count()];
            }
        }
        $cutoff = CarbonImmutable::today()->subMonthsNoOverflow(12)->toDateString();
        $last12 = $family->filter(fn ($r) => $r['opened_at'] && $r['opened_at'] >= $cutoff && $r['opened_at'] <= CarbonImmutable::today()->toDateString());
        $interval = $dates->count() > 1 ? round(CarbonImmutable::parse($dates->first())->diffInDays(CarbonImmutable::parse($dates->last())) / ($dates->count() - 1), 1) : null;
        return compact('groups', 'suggestions', 'group', 'search', 'from', 'to', 'rows', 'months', 'interval') + [
            'updatedAt' => $snapshot['exported_at'], 'totalSource' => $all->count(),
            'quantity' => $rows->sum('quantity'), 'last12' => $last12->sum('quantity'),
            'reviewCount' => $rows->where('quantity_review', true)->count(),
            'undated' => $family->whereNull('opened_at')->count(),
        ];
    }
}
