<?php

namespace App\Support;

use App\Models\Code;
use App\Models\Component;
use App\Models\LogCard;
use App\Models\Workorder;

final class LogCardDestructionCertificate
{
    /**
     * Log Card rows that qualify for Certificate of Destruction (reason code flagged in admin).
     *
     * @return list<array{description: string, part_number: string, serial_number: string}>
     */
    public static function rowsForWorkorder(Workorder $current_wo): array
    {
        $logCard = LogCard::where('workorder_id', $current_wo->id)->first();
        if (! $logCard || ! $logCard->component_data) {
            return [];
        }

        $componentData = is_array($logCard->component_data)
            ? $logCard->component_data
            : json_decode($logCard->component_data, true);

        if (! is_array($componentData)) {
            return [];
        }

        $destructionCodeIds = Code::query()
            ->where('requires_destruction_cert', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($destructionCodeIds === []) {
            return [];
        }

        $manualId = $current_wo->unit->manual_id;
        $components = Component::where('manual_id', $manualId)->get()->keyBy(fn ($c) => (int) $c->id);

        $rows = [];
        foreach ($componentData as $item) {
            if (! is_array($item)) {
                continue;
            }
            $reason = $item['reason'] ?? null;
            if ($reason === null || $reason === '') {
                continue;
            }
            $rid = (int) $reason;
            if (! in_array($rid, $destructionCodeIds, true)) {
                continue;
            }

            $cid = $item['component_id'] ?? null;
            if ($cid === null || $cid === '') {
                continue;
            }

            $comp = $components->get((int) $cid);
            if (! $comp) {
                continue;
            }

            $hasSerialNumber = ! empty($item['serial_number']);
            $hasAssySerialNumber = isset($item['assy_serial_number']) && ! empty($item['assy_serial_number']);

            $rows[] = [
                'description' => (string) ($comp->name ?? ''),
                'part_number' => self::formatPartNumberCell($comp, $hasSerialNumber, $hasAssySerialNumber),
                'serial_number' => self::formatSerialCell($item, $hasSerialNumber, $hasAssySerialNumber),
            ];
        }

        return $rows;
    }

    public static function availableFor(Workorder $current_wo): bool
    {
        return self::rowsForWorkorder($current_wo) !== [];
    }

    private static function formatPartNumberCell(Component $comp, bool $hasSerialNumber, bool $hasAssySerialNumber): string
    {
        if ($hasAssySerialNumber && ! $hasSerialNumber) {
            return (string) ($comp->assy_part_number ?? '');
        }
        if ($hasAssySerialNumber && $hasSerialNumber) {
            $pn = $comp->part_number ?? '';
            $apn = $comp->assy_part_number ?? '';

            return $pn.($apn !== '' && $apn !== null ? "\n(".$apn.')' : '');
        }

        return (string) ($comp->part_number ?? '');
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
}
