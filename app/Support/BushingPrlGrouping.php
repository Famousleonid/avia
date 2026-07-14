<?php

namespace App\Support;

use App\Models\Component;

class BushingPrlGrouping
{
    public static function groupKeyForComponent(Component $component): string
    {
        $manualId = (int) ($component->manual_id ?? 0);
        $bushIpl = strtoupper(trim((string) ($component->bush_ipl_num ?? '')));

        if ($bushIpl !== '') {
            return 'bushing|' . $manualId . '|' . $bushIpl;
        }

        return 'component|' . $manualId . '|' . (int) ($component->id ?? 0);
    }
}
