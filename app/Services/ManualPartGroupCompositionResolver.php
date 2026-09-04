<?php

namespace App\Services;

use App\Models\ManualPartGroup;
use Illuminate\Support\Collection;

class ManualPartGroupCompositionResolver
{
    /**
     * Resolve every component contained by each group, including nested ASSY
     * and Bushing Original/Oversize groups.
     *
     * @param  Collection<int, ManualPartGroup>  $groups
     * @return array<int, Collection<int, int>>
     */
    public function componentIdsByGroup(Collection $groups): array
    {
        $groupsById = $groups->keyBy(fn (ManualPartGroup $group): int => (int) $group->id);
        $optionToGroupId = $groups
            ->flatMap(fn (ManualPartGroup $group) => $group->options)
            ->mapWithKeys(fn ($option): array => [(int) $option->id => (int) $option->manual_part_group_id]);
        $memo = [];

        foreach ($groupsById as $groupId => $group) {
            $this->resolveGroup((int) $groupId, $groupsById, $optionToGroupId, $memo, []);
        }

        return $memo;
    }

    /**
     * @param  Collection<int, ManualPartGroup>  $groupsById
     * @param  Collection<int, int>  $optionToGroupId
     * @param  array<int, Collection<int, int>>  $memo
     * @param  array<int, bool>  $visiting
     * @return Collection<int, int>
     */
    private function resolveGroup(
        int $groupId,
        Collection $groupsById,
        Collection $optionToGroupId,
        array &$memo,
        array $visiting
    ): Collection {
        if (isset($memo[$groupId])) {
            return $memo[$groupId];
        }
        if (isset($visiting[$groupId])) {
            return collect();
        }

        /** @var ManualPartGroup|null $group */
        $group = $groupsById->get($groupId);
        if (! $group) {
            return collect();
        }

        $visiting[$groupId] = true;
        $componentIds = collect();

        foreach ($group->options as $option) {
            if ((int) ($option->component_id ?? 0) > 0) {
                $componentIds->push((int) $option->component_id);
            }

            foreach ($option->coverages as $coverage) {
                if ((int) ($coverage->component_id ?? 0) > 0) {
                    $componentIds->push((int) $coverage->component_id);
                }

                $nestedGroupId = (int) ($optionToGroupId->get(
                    (int) ($coverage->covered_manual_part_group_option_id ?? 0)
                ) ?? 0);
                if ($nestedGroupId > 0) {
                    $componentIds = $componentIds->merge(
                        $this->resolveGroup($nestedGroupId, $groupsById, $optionToGroupId, $memo, $visiting)
                    );
                }
            }
        }

        return $memo[$groupId] = $componentIds
            ->map(fn ($componentId): int => (int) $componentId)
            ->filter(fn (int $componentId): bool => $componentId > 0)
            ->unique()
            ->values();
    }
}
