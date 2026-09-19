<?php

namespace App\Support;

use App\Models\Component;
use App\Services\PartVariantGrouping;

class KitPrlGrouping
{
    public static function groupKeyForComponent(Component $component, array $explicitKeys = []): string
    {
        $ipl = (string) ($component->ipl_num ?? '');
        $manualId = (int) ($component->manual_id ?? 0);
        if (isset($explicitKeys[(int) $component->id])) {
            return $explicitKeys[(int) $component->id];
        }

        return 'numeric|' . $manualId . '|' . self::numericIplGroupKey($ipl, (int) ($component->id ?? 0));
    }

    public static function numericIplGroupKey(string $ipl, ?int $componentId = null): string
    {
        $normalized = PartVariantGrouping::iplFamily($ipl);
        return $normalized !== '' ? $normalized : 'component-' . (string) ($componentId ?? 0);
    }

}
