<?php

namespace App\Services;

use App\Models\ProcessName;

class ProcessPrintAttachment
{
    public function forForm(?ProcessName $process, iterable $rows, iterable $tableData = []): ?array
    {
        if (!$process) return null;
        $components = collect($rows)->map(fn ($row) => $row->tdr?->component)
            ->merge(collect($tableData)->map(fn ($row) => $row['component'] ?? null));
        if ($components->isEmpty() || $components->contains(fn ($part) =>
            ! $part || ! data_get($part, 'manual_id') || trim((string) data_get($part, 'part_number')) === '')) return null;
        $parts = $components->map(fn ($part) => [
            'manual_id' => (int) $part->manual_id,
            'part_number' => ProcessName::printPartKey($part->part_number),
        ])->unique(fn ($part) => $part['manual_id'].'|'.$part['part_number'])->values();
        // Same P/N in different manuals is still a mixed form: never choose arbitrarily.
        if ($parts->count() !== 1) return null;
        $media = $process->printAttachmentFor($parts[0]['part_number'], $parts[0]['manual_id']);
        return $media ? ['media' => $media] + $parts[0] : null;
    }
}
