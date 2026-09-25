<?php

namespace App\Services;

use App\Models\ManualPartGroup;
use App\Models\ManualPartGroupCoverage;
use App\Models\ManualPartGroupOption;
use App\Models\Tdr;
use App\Models\Unit;
use App\Models\Workorder;
use App\Models\WorkorderAssyConfigurationChoice;
use Illuminate\Support\Collection;

/**
 * A choice selects one catalog coverage inside an ordered/received ASSY.
 * It does not order the child as another TDR part.
 */
class WorkorderAssyConfiguration
{
    /** @return array<string, int> */
    public function selectedCoverageIds(Workorder $workorder): array
    {
        return WorkorderAssyConfigurationChoice::query()
            ->where('workorder_id', $workorder->id)
            ->get(['parent_option_id', 'choice_slot', 'selected_coverage_id'])
            ->mapWithKeys(fn ($choice): array => [
                self::key((int) $choice->parent_option_id, (string) $choice->choice_slot)
                    => (int) $choice->selected_coverage_id,
            ])->all();
    }

    public static function key(int $parentOptionId, string $slot): string
    {
        return $parentOptionId.'|'.$slot;
    }

    /**
     * Fail closed on an unselected or stale multi-candidate slot. Old additive
     * coverages have no choice_slot and retain their existing behavior.
     *
     * @param  array<string, int>  $selected
     * @return Collection<int, ManualPartGroupCoverage>
     */
    public function members(ManualPartGroupOption $option, array $selected, ?string $scope = null): Collection
    {
        $members = $option->coverages;
        $chosen = $members->groupBy(fn (ManualPartGroupCoverage $member): string =>
            filled($member->choice_slot) ? 'slot:'.$member->choice_slot : 'edge:'.$member->id
        )->flatMap(function (Collection $candidates) use ($option, $selected): Collection {
            $slot = trim((string) $candidates->first()->choice_slot);
            if ($slot === '' || $candidates->count() === 1) {
                return $candidates;
            }

            $chosenId = $selected[self::key((int) $option->id, $slot)] ?? 0;

            return $candidates->filter(fn (ManualPartGroupCoverage $candidate): bool =>
                (int) $candidate->id === $chosenId
            );
        });

        return ($scope === null ? $chosen : $chosen->filter(
            fn (ManualPartGroupCoverage $member): bool => $member->appliesTo($scope)
        ))->values();
    }

    /** @return Collection<int, ManualPartGroupOption> */
    public function rootOptions(Workorder $workorder): Collection
    {
        $ids = array_filter([
            (int) ($workorder->scope_type === Unit::SCOPE_PART_GROUP_OPTION
                ? $workorder->scope_part_group_option_id : 0),
            (int) ($workorder->modified_scope_part_group_option_id ?? 0),
        ]);
        $orderedComponentIds = Tdr::query()
            ->where('workorder_id', $workorder->id)
            ->whereNotNull('order_component_id')
            ->pluck('order_component_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return ManualPartGroupOption::query()
            ->whereHas('group', fn ($query) => $query
                ->whereIn('manual_id', $workorder->usedManualIds())
                ->where('type', ManualPartGroup::TYPE_ASSY))
            ->where(fn ($query) => $query->whereIn('id', $ids)
                ->orWhereIn('component_id', $orderedComponentIds))
            ->with(['group', 'coverages.coveredOption.group', 'coverages.component'])
            ->get();
    }

    /**
     * Slots reachable from a received or ordered ASSY. An unselected slot is
     * shown once; its child branches are not guessed until it is selected.
     *
     * @return array<int, array<string, mixed>>
     */
    public function slots(Workorder $workorder): array
    {
        $selected = $this->selectedCoverageIds($workorder);
        $slots = [];
        $visited = [];
        $visit = function (ManualPartGroupOption $option) use (&$visit, &$slots, &$visited, $selected): void {
            if (isset($visited[$option->id])) {
                return;
            }
            $visited[$option->id] = true;
            $option->loadMissing(['group', 'coverages.coveredOption.group', 'coverages.component']);

            foreach ($option->coverages->groupBy(fn ($coverage): string =>
                filled($coverage->choice_slot) ? 'slot:'.$coverage->choice_slot : 'edge:'.$coverage->id
            ) as $candidates) {
                $slot = trim((string) $candidates->first()->choice_slot);
                if ($slot !== '' && $candidates->count() > 1) {
                    $chosenId = $selected[self::key((int) $option->id, $slot)] ?? 0;
                    $slots[] = [
                        'parent_option_id' => (int) $option->id,
                        'parent_part_number' => (string) $option->part_number,
                        'slot' => $slot,
                        'selected_coverage_id' => $chosenId,
                        'candidates' => $candidates->map(fn ($coverage): array => [
                            'coverage_id' => (int) $coverage->id,
                            'option_id' => (int) ($coverage->covered_manual_part_group_option_id ?? 0),
                            'component_id' => (int) ($coverage->component_id ?? 0),
                            'part_number' => (string) ($coverage->coveredOption?->part_number
                                ?? $coverage->component?->part_number ?? ''),
                            'ipl' => (string) ($coverage->coveredOption?->ipl_num
                                ?? $coverage->component?->ipl_num ?? ''),
                        ])->values()->all(),
                    ];
                }

                foreach ($this->members($option, $selected) as $active) {
                    if ($candidates->contains('id', $active->id) && $active->coveredOption) {
                        $visit($active->coveredOption);
                    }
                }
            }
        };

        foreach ($this->rootOptions($workorder) as $root) {
            $visit($root);
        }

        return $slots;
    }
}
