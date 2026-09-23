<?php

namespace App\Services;

use App\Models\ManualPartGroup;
use App\Models\Component;
use Illuminate\Validation\ValidationException;

/** Shared display identity for KIT, STD forms and their counters. */
class PartVariantGrouping
{
    public static function iplFamily(string $ipl): string
    {
        $ipl = strtoupper(trim($ipl));
        return preg_replace('/(?<=\d)[A-Z]+$/', '', $ipl) ?? $ipl;
    }

    /** @return array<int, string> Component ID => explicit group identity. */
    public function explicitKeys(array $manualIds, string $scope): array
    {
        $keys = [];
        if ($manualIds === []) {
            return $keys;
        }
        $groups = ManualPartGroup::query()
            ->whereIn('manual_id', array_unique($manualIds))
            ->whereIn('type', [ManualPartGroup::TYPE_ALTERNATIVE, ManualPartGroup::TYPE_OVERSIZE])
            ->with('options:id,manual_part_group_id,component_id')
            ->get();
        foreach ($groups as $group) {
            if (! ($scope === 'bushing' && $group->type === ManualPartGroup::TYPE_OVERSIZE)
                && ! $group->appliesTo($scope === 'bushing' ? 'prl' : $scope)) {
                continue;
            }
            foreach ($group->options as $option) {
                $id = (int) $option->component_id;
                if ($id <= 0) {
                    continue;
                }
                $key = 'part-group|'.$group->manual_id.'|'.$group->id;
                if (isset($keys[$id]) && $keys[$id] !== $key) {
                    throw ValidationException::withMessages([
                        'part_groups' => "Component #{$id} belongs to multiple variant groups. Review Part Groups before printing.",
                    ]);
                }
                $keys[$id] = $key;
            }
        }
        // Letter variants inherit a position's explicit alternative identity.
        // Do not arbitrarily bridge two explicitly different bushing families.
        $parts = Component::whereIn('manual_id', array_unique($manualIds))->get(['id', 'manual_id', 'ipl_num']);
        foreach ($parts->groupBy(fn ($p) => $p->manual_id.'|'.self::iplFamily($p->ipl_num)) as $family) {
            $familyKeys = $family->map(fn ($p) => $keys[(int) $p->id] ?? null)->filter()->unique();
            if ($familyKeys->count() === 1) {
                foreach ($family as $part) {
                    $keys[(int) $part->id] ??= $familyKeys->first();
                }
            }
        }
        return $keys;
    }

    /** No P/N-based merging: identical P/Ns at different IPLs remain separate. */
    public function componentKeys($components, string $scope = 'prl'): array
    {
        $components = collect($components);
        $explicit = $this->explicitKeys($components->pluck('manual_id')->filter()->all(), $scope);
        return $components->mapWithKeys(function ($component) use ($explicit): array {
            $id = (int) $component->id;
            $family = self::iplFamily((string) $component->ipl_num);
            return [$id => $explicit[$id] ?? 'ipl|'.$component->manual_id.'|'.($family ?: 'component-'.$id)];
        })->all();
    }

    public function annotateStdRows(array $rows, string $scope): array
    {
        $explicit = $this->explicitKeys(array_column($rows, 'manual_id'), $scope);
        foreach ($rows as &$row) {
            $row['variant_group_key'] = $explicit[(int) ($row['component_id'] ?? 0)] ?? null;
            unset($row['kit_prl_choice_group']);
        }
        unset($row);
        return $rows;
    }

    public static function stdKey(array $row): string
    {
        $manual = (int) ($row['manual_id'] ?? 0) > 0
            ? 'id:'.$row['manual_id'] : 'number:'.trim((string) ($row['manual'] ?? ''));
        $identity = trim((string) ($row['variant_group_key'] ?? ''));
        if ($identity === '') {
            $family = self::iplFamily((string) ($row['ipl_num'] ?? ''));
            $identity = 'ipl|'.$manual.'|'.($family ?: 'component-'.($row['component_id'] ?? 0))
                .'|'.trim((string) ($row['process'] ?? ''));
        }
        return $identity.'|'.(! empty($row['group_crossed_out']) ? 'crossed' : 'open');
    }
}
