<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class LogCardPayloadProtectionService
{
    /**
     * Fields owned by the ordinary Log Card workflow. QA-owned fields are never
     * accepted from this payload; they can only be changed by the QA endpoint.
     */
    private const COMPONENT_WRITABLE_FIELDS = [
        'component_id',
        'included',
        'serial_number',
        'assy_serial_number',
        'reason',
        'new_serial_number',
        'manual_id',
        'ipl_group',
        'component_assembly_id',
        'manual_part_group_id',
        'manual_part_group_option_id',
        'manual_part_group_choice',
        'unit_index',
        'units_assy',
    ];

    private const MANUAL_WRITABLE_FIELDS = [
        'row_type',
        'manual_id',
        'manual_label',
    ];

    public function protectCreate(array $incomingRows): array
    {
        return collect($incomingRows)
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row) => $this->ordinaryFields($row))
            ->values()
            ->all();
    }

    public function protectUpdate(array $incomingRows, array $storedRows): array
    {
        $storedByKey = [];
        foreach ($storedRows as $index => $storedRow) {
            if (! is_array($storedRow)) {
                continue;
            }

            $storedByKey[$this->rowKey($storedRow, $index)] = [
                'row' => $storedRow,
                'matched' => false,
            ];
        }

        $protectedRows = [];
        foreach (array_values($incomingRows) as $index => $incomingRow) {
            if (! is_array($incomingRow)) {
                continue;
            }

            $key = $this->rowKey($incomingRow, $index);
            $stored = $storedByKey[$key]['row'] ?? [];
            if (isset($storedByKey[$key])) {
                $storedByKey[$key]['matched'] = true;
            }

            // Start from the latest database row. This preserves QA fields and
            // any future server-owned fields even when the browser/mobile client
            // submits an old or incomplete representation.
            $protectedRows[] = array_replace($stored, $this->ordinaryFields($incomingRow));
        }

        foreach ($storedByKey as $entry) {
            if (! $entry['matched'] && $this->containsQaOwnedData($entry['row'])) {
                throw ValidationException::withMessages([
                    'component_data' => __('A Log Card row containing QA data cannot be removed from the ordinary Log Card. Reload the page or contact Quality Assurance.'),
                ]);
            }
        }

        return $protectedRows;
    }

    public function containsQaOwnedData(array $row): bool
    {
        foreach ($row as $key => $value) {
            if (str_starts_with((string) $key, 'qa_')) {
                return true;
            }
        }

        return false;
    }

    public function logCardContainsQaOwnedData(mixed $componentData, mixed $componentDataOut, mixed $certificateData): bool
    {
        foreach ([$this->decodeRows($componentData), $this->decodeRows($componentDataOut)] as $rows) {
            foreach ($rows as $row) {
                if (is_array($row) && $this->containsQaOwnedData($row)) {
                    return true;
                }
            }
        }

        if (is_string($certificateData)) {
            $certificateData = json_decode($certificateData, true);
        }

        return is_array($certificateData) && $certificateData !== [];
    }

    private function ordinaryFields(array $row): array
    {
        $allowed = ($row['row_type'] ?? '') === 'manual'
            ? self::MANUAL_WRITABLE_FIELDS
            : self::COMPONENT_WRITABLE_FIELDS;

        return array_intersect_key($row, array_flip($allowed));
    }

    private function rowKey(array $row, int $fallback): string
    {
        if (($row['row_type'] ?? '') === 'manual') {
            return 'manual:'.(string) ($row['manual_id'] ?? $row['manual_label'] ?? $fallback);
        }

        $manualId = (string) ($row['manual_id'] ?? '');
        $unitIndex = (string) ($row['unit_index'] ?? '');
        $componentId = (string) ($row['component_id'] ?? '');
        if ($unitIndex !== '') {
            return "unit:{$manualId}:{$componentId}:{$unitIndex}";
        }

        $iplGroup = (string) ($row['ipl_group'] ?? '');
        if ($iplGroup !== '') {
            return "group:{$manualId}:{$iplGroup}";
        }

        return "component:{$manualId}:{$componentId}";
    }

    private function decodeRows(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
    }
}
