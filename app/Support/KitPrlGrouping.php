<?php

namespace App\Support;

use App\Models\Component;

class KitPrlGrouping
{
    public static function groupKeyForComponent(Component $component): string
    {
        $ipl = (string) ($component->ipl_num ?? '');
        $manualId = (int) ($component->manual_id ?? 0);
        $choiceGroup = self::normalizeChoiceGroup((string) ($component->kit_prl_choice_group ?? ''));

        if ($choiceGroup !== '') {
            return 'choice|' . $manualId . '|' . mb_strtolower($choiceGroup);
        }

        return 'numeric|' . self::numericIplGroupKey($ipl, (int) ($component->id ?? 0));
    }

    public static function numericIplGroupKey(string $ipl, ?int $componentId = null): string
    {
        $normalized = strtoupper(trim($ipl));
        $withoutSuffix = preg_replace('/([0-9])[^0-9-]*$/', '$1', $normalized) ?? $normalized;
        $digitsOnly = preg_replace('/[^0-9]+/', '-', $withoutSuffix) ?? $withoutSuffix;
        $digitsOnly = trim($digitsOnly, '-');

        if ($digitsOnly !== '') {
            return $digitsOnly;
        }

        return $normalized !== '' ? $normalized : 'component-' . (string) ($componentId ?? 0);
    }

    private static function normalizeChoiceGroup(string $choiceGroup): string
    {
        return trim($choiceGroup);
    }
}
