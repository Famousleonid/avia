<?php

namespace App\Support;

use App\Models\LogCard;
use App\Models\Workorder;
use App\Models\Component;
use Carbon\Carbon;

final class LogCardDestructionCertificate
{
    /**
     * Log Card components available for Certificate of Destruction.
     *
     * @return list<array{key: string, selected: bool, description: string, part_number: string, serial_number: string}>
     */
    public static function rowsForWorkorder(Workorder $current_wo): array
    {
        $logCard = self::logCardForWorkorder($current_wo);
        if (! $logCard || ! $logCard->component_data) {
            return [];
        }

        $componentData = self::decodeJsonish($logCard->component_data);

        if (! is_array($componentData)) {
            return [];
        }

        $settings = self::settingsFor($logCard);
        $selectedKeys = $settings['selected_keys'] ?? null;
        $componentIds = collect($componentData)
            ->filter(fn ($item) => is_array($item) && filled($item['component_id'] ?? null))
            ->map(fn ($item) => (int) $item['component_id'])
            ->unique()
            ->values();
        $components = Component::whereIn('id', $componentIds)->get()->keyBy(fn (Component $component) => (int) $component->id);

        $rows = [];
        foreach (array_values($componentData) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $hasSerialNumber = ! blank($item['serial_number'] ?? null);
            $hasAssySerialNumber = ! blank($item['assy_serial_number'] ?? null);
            $key = self::rowKey($item, $index);
            $component = filled($item['component_id'] ?? null)
                ? $components->get((int) $item['component_id'])
                : null;

            $rows[] = [
                'key' => $key,
                'selected' => is_array($selectedKeys) ? in_array($key, $selectedKeys, true) : false,
                'description' => (string) ($item['name'] ?? $item['description'] ?? $component?->name ?? ''),
                'part_number' => self::formatPartNumberCell($item, $component, $hasSerialNumber, $hasAssySerialNumber),
                'serial_number' => self::formatSerialCell($item, $hasSerialNumber, $hasAssySerialNumber),
            ];
        }

        return $rows;
    }

    public static function availableFor(Workorder $current_wo): bool
    {
        return self::rowsForWorkorder($current_wo) !== [];
    }

    public static function logCardForWorkorder(Workorder $current_wo): ?LogCard
    {
        return LogCard::where('workorder_id', $current_wo->id)->first();
    }

    /**
     * @return array{selected_keys?: list<string>, certificate_date?: string, manual_selected?: bool, manual_row?: array{part_number?: string, description?: string, serial_number?: string}}
     */
    public static function settingsFor(?LogCard $logCard): array
    {
        if (! $logCard) {
            return [];
        }

        $settings = self::decodeJsonish($logCard->destruction_certificate_data);

        return is_array($settings) ? $settings : [];
    }

    /**
     * @return array{part_number: string, description: string, serial_number: string}
     */
    public static function manualRowFor(?LogCard $logCard): array
    {
        $manualRow = self::settingsFor($logCard)['manual_row'] ?? [];

        return [
            'part_number' => (string) ($manualRow['part_number'] ?? ''),
            'description' => (string) ($manualRow['description'] ?? ''),
            'serial_number' => (string) ($manualRow['serial_number'] ?? ''),
        ];
    }

    public static function manualSelectedFor(?LogCard $logCard): bool
    {
        return (bool) (self::settingsFor($logCard)['manual_selected'] ?? false);
    }

    public static function certificateDateFor(?LogCard $logCard): string
    {
        $savedDate = self::settingsFor($logCard)['certificate_date'] ?? null;

        return is_string($savedDate) && trim($savedDate) !== ''
            ? trim($savedDate)
            : Carbon::now()->format('d/M/Y');
    }

    public static function normalizeCertificateData(array $data): array
    {
        $selectedKeys = collect($data['selected_keys'] ?? [])
            ->filter(fn ($key) => is_string($key) && $key !== '')
            ->values()
            ->all();
        $manualRow = is_array($data['manual_row'] ?? null) ? $data['manual_row'] : [];

        return [
            'selected_keys' => $selectedKeys,
            'certificate_date' => self::normalizeDate((string) ($data['certificate_date'] ?? '')),
            'manual_selected' => (bool) ($data['manual_selected'] ?? false),
            'manual_row' => [
                'part_number' => trim((string) ($manualRow['part_number'] ?? '')),
                'description' => trim((string) ($manualRow['description'] ?? '')),
                'serial_number' => trim((string) ($manualRow['serial_number'] ?? '')),
            ],
        ];
    }

    private static function normalizeDate(string $date): string
    {
        $date = trim($date);

        return $date !== '' ? $date : Carbon::now()->format('d/M/Y');
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function formatPartNumberCell(array $item, ?Component $component, bool $hasSerialNumber, bool $hasAssySerialNumber): string
    {
        if ($hasAssySerialNumber && ! $hasSerialNumber) {
            return (string) ($item['assy_part_number'] ?? $component?->assy_part_number ?? '');
        }
        if ($hasAssySerialNumber && $hasSerialNumber) {
            $pn = $item['part_number'] ?? $component?->part_number ?? '';
            $apn = $item['assy_part_number'] ?? $component?->assy_part_number ?? '';

            return $pn.($apn !== '' && $apn !== null ? "\n(".$apn.')' : '');
        }

        return (string) ($item['part_number'] ?? $component?->part_number ?? '');
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function formatSerialCell(array $item, bool $hasSerialNumber, bool $hasAssySerialNumber): string
    {
        if ($hasAssySerialNumber && ! $hasSerialNumber) {
            return (string) ($item['assy_serial_number'] ?? '');
        }
        if ($hasAssySerialNumber && $hasSerialNumber) {
            $sn = (string) ($item['serial_number'] ?? '');
            $asy = (string) ($item['assy_serial_number'] ?? '');

            return $sn.($asy !== '' ? "\n(".$asy.')' : '');
        }

        return (string) ($item['serial_number'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function rowKey(array $item, int $index): string
    {
        return implode('|', [
            $item['component_id'] ?? 'row',
            trim((string) ($item['part_number'] ?? '')),
            trim((string) ($item['serial_number'] ?? '')),
            trim((string) ($item['assy_serial_number'] ?? '')),
            $index,
        ]);
    }

    private static function decodeJsonish(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        return json_decode($value, true);
    }
}
