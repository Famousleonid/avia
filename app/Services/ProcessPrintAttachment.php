<?php

namespace App\Services;

use App\Models\ProcessName;

class ProcessPrintAttachment
{
    public function forForm(?ProcessName $process, iterable $rows, iterable $tableData = []): ?array
    {
        if (!$process) return null;
        $parts = collect($rows)->map(fn ($row) => $row->tdr?->component?->part_number)
            ->merge(collect($tableData)->map(fn ($row) => $row['component']->part_number ?? null))
            ->filter()->map(fn ($pn) => ProcessName::printPartKey($pn))->unique()->values();
        // Only a form for one concrete part number may acquire an attachment.
        if ($parts->count() !== 1) return null;
        $media = $process->printAttachmentFor($parts[0]);
        return $media ? ['media' => $media, 'part_number' => $parts[0]] : null;
    }
}
