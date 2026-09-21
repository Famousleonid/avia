<?php

namespace App\Support;

use App\Models\Component;

class BushingPrlGrouping
{
    public static function capacity($components): int
    {
        $initial = $components->first(fn (Component $component): bool =>
            trim((string) $component->bush_ipl_num) !== ''
            && trim((string) $component->ipl_num) === trim((string) $component->bush_ipl_num)
        );

        return max(1, (int) ($initial?->units_assy ?? $components->max('units_assy') ?? 1));
    }

    public static function groups($components)
    {
        $keys = app(\App\Services\PartVariantGrouping::class)->explicitKeys($components->pluck('manual_id')->all(), 'prl');

        return $components->groupBy(fn (Component $component): string => self::groupKeyForComponent($component, $keys));
    }

    /** Allocate a single paid family budget, never a separate allowance per P/N. */
    public static function kitAllocation($components, $selected): array
    {
        $remaining = self::capacity($components);
        $allocation = [];
        foreach ($components->sortBy('id') as $component) {
            if (! $selected->has($component->id)) {
                continue;
            }
            $qty = min($remaining, max(1, (int) $selected->get($component->id)['qty']));
            $allocation[(int) $component->id] = $qty;
            $remaining -= $qty;
        }

        return $allocation;
    }

    public static function groupKeyForComponent(Component $component, array $explicitKeys = []): string
    {
        if (isset($explicitKeys[(int) $component->id])) {
            return $explicitKeys[(int) $component->id];
        }
        $manualId = (int) ($component->manual_id ?? 0);
        $bushIpl = strtoupper(trim((string) ($component->bush_ipl_num ?? '')));

        $bushIpl = $bushIpl !== '' ? $bushIpl : strtoupper(trim((string) $component->ipl_num));
        if ($bushIpl !== '') {
            return 'bushing|' . $manualId . '|' . $bushIpl;
        }

        return 'component|' . $manualId . '|' . (int) ($component->id ?? 0);
    }
}
