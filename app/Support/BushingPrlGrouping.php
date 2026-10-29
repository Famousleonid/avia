<?php

namespace App\Support;

use App\Models\Component;

class BushingPrlGrouping
{
    /** Classify without rewriting the stored legacy flag. */
    public static function candidates($components)
    {
        $ids = Component::query()->whereKey($components->pluck('id'))
            ->bushingCandidates()->pluck('id');

        return $components->whereIn('id', $ids)->values();
    }

    /** Read once for all families, retaining the original per-family option order. */
    public static function capacityOptions($components): \Illuminate\Support\Collection
    {
        return \App\Models\ManualPartGroupOption::query()
            ->whereIn('component_id', $components->pluck('id')->unique())
            ->whereHas('group', fn ($q) => $q->where('type', \App\Models\ManualPartGroup::TYPE_OVERSIZE))
            ->orderBy('sort_order')->orderBy('id')->get();
    }

    public static function capacity($components, ?\Illuminate\Support\Collection $options = null): int
    {
        $options = ($options ?? self::capacityOptions($components))
            ->whereIn('component_id', $components->pluck('id'));
        $original = self::originalComponent($components, $options);
        if ($original && $options->contains(fn ($option) =>
            (int) $option->component_id === (int) $original->id
            && ($option->option_kind === 'original'
                || ($option->option_kind === 'standard' && is_numeric($original->units_assy)))
        )) {
            return max(1, (int) $original->units_assy);
        }

        return max(1, (int) ($original?->units_assy ?? $components->max('units_assy') ?? 1));
    }

    public static function originalComponent($components, ?\Illuminate\Support\Collection $options = null): ?Component
    {
        // Imported groups may predate option_kind=original. Their ordered first
        // numeric standard option is the original; never use oversize AR as its limit.
        $options = ($options ?? self::capacityOptions($components))
            ->whereIn('component_id', $components->pluck('id'));
        $original = $options->firstWhere('option_kind', 'original')
            ?? $options->first(fn ($option) => $option->option_kind === 'standard'
                && is_numeric($components->firstWhere('id', $option->component_id)?->units_assy));
        if ($original) {
            return $components->firstWhere('id', $original->component_id);
        }

        return $components->first(fn (Component $component): bool =>
            trim((string) $component->bush_ipl_num) !== ''
            && trim((string) $component->ipl_num) === trim((string) $component->bush_ipl_num)
        );
    }

    public static function groups($components, ?\App\Services\PartVariantGrouping $grouping = null)
    {
        $keys = ($grouping ?? app(\App\Services\PartVariantGrouping::class))->explicitKeys($components->pluck('manual_id')->all(), 'bushing');

        return $components->groupBy(fn (Component $component): string => self::groupKeyForComponent($component, $keys));
    }

    /** Allocate a single paid family budget, never a separate allowance per P/N. */
    public static function kitAllocation($components, $selected, ?int $capacity = null): array
    {
        $remaining = $capacity ?? self::capacity($components);
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
