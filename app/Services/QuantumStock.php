<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class QuantumStock
{
    public function path(): string { return storage_path('app/quantum/stock.json'); }

    public function read(): array
    {
        return is_file($this->path()) ? json_decode(file_get_contents($this->path()), true, 512, JSON_THROW_ON_ERROR)
            : ['exported_at' => null, 'row_count' => 0, 'rows' => []];
    }

    public function import(array $payload): void
    {
        $payload = Validator::make($payload, [
            'version' => 'required|in:1,2', 'exported_at' => 'required|date',
            'row_count' => 'required|integer|min:1|max:100000', 'rows' => 'required|array|min:1|max:100000',
        ])->validate();
        if (count($payload['rows']) !== (int) $payload['row_count']) {
            throw ValidationException::withMessages(['rows' => 'Incomplete stock snapshot.']);
        }
        $ids = [];
        foreach ($payload['rows'] as &$row) {
            $row = Validator::make($row, [
                'source_id' => 'required|integer|min:1', 'part_number' => 'required|string|max:255',
                'description' => 'present|nullable|string|max:4000',
                'on_hand' => 'required|numeric', 'reserved' => 'required|numeric', 'available' => 'required|numeric',
                'reservations' => ((int)$payload['version']===2?'present':'sometimes').'|array',
                'reservations.*.wo' => 'required|string|max:100|distinct',
                'reservations.*.quantity' => 'required|numeric|gt:0',
                'unassigned_reserved' => ((int)$payload['version']===2?'required':'sometimes').'|numeric|min:0',
            ])->validate();
            if (isset($ids[$row['source_id']])) { throw ValidationException::withMessages(['rows' => 'Duplicate stock identity.']); }
            $ids[$row['source_id']] = true;
        }
        unset($row);
        $this->locked(function () use ($payload) {
            $previous = $this->read();
            $time = CarbonImmutable::parse($payload['exported_at']);
            if ($time->isAfter(CarbonImmutable::now()->addMinutes(5)) ||
                ($previous['exported_at'] && $time->isBefore(CarbonImmutable::parse($previous['exported_at'])))) {
                throw ValidationException::withMessages(['exported_at' => 'Stale or future stock snapshot.']);
            }
            File::replace($this->path(), json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        });
    }

    public function search(string $pn, string $description, string $query = '', bool $nonzero = false): array
    {
        $snapshot = $this->read();
        $pn = mb_strtoupper(trim($pn)); $description = mb_strtoupper(trim($description));
        $query = mb_strtoupper(trim($query));
        $snapshot['rows'] = array_values(array_filter($snapshot['rows'], static fn ($row) =>
            ($pn === '' || str_starts_with(mb_strtoupper($row['part_number']), $pn)) &&
            ($description === '' || str_contains(mb_strtoupper($row['description'] ?? ''), $description)) &&
            (!$nonzero || (float)$row['on_hand'] != 0) &&
            ($query === '' || str_starts_with(mb_strtoupper($row['part_number']), $query) || str_contains(mb_strtoupper($row['description'] ?? ''), $query))));
        usort($snapshot['rows'], static fn ($a, $b) => strnatcasecmp($a['part_number'], $b['part_number']) ?: $a['source_id'] <=> $b['source_id']);
        return $snapshot;
    }

    private function locked(callable $callback)
    {
        File::ensureDirectoryExists(dirname($this->path()));
        $lock = fopen($this->path() . '.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) { throw new \RuntimeException('Cannot lock stock snapshot'); }
        try { return $callback(); } finally { flock($lock, LOCK_UN); fclose($lock); }
    }
}
