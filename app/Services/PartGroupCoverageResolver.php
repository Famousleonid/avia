<?php

namespace App\Services;

use App\Models\ManualPartGroup;
use App\Models\ManualPartGroupOption;
use App\Models\Tdr;
use App\Models\Workorder;
use App\Models\WorkorderPartGroupSelection;
use App\Models\WoBushingLine;
use Illuminate\Support\Collection;

class PartGroupCoverageResolver
{
    /**
     * @return array<int, array{covered_qty:int, reason:string, group_id:int, option_id:int}>
     */
    public function coverageForWorkorder(Workorder $workorder, string $scope): array
    {
        if (! in_array($scope, ManualPartGroup::validScopes(), true)) {
            throw new \InvalidArgumentException("Invalid part group scope: {$scope}");
        }

        $groups = ManualPartGroup::query()
            ->whereIn('manual_id', $workorder->usedManualIds())
            ->with([
                'options.coverages.coveredOption.coverages',
                'options.coverages.coveredOption.group',
                'serviceBulletin:id,ac_mfg_service_bulletin_no,oem_service_bulletin_no',
            ])
            ->get()
            ->filter(fn (ManualPartGroup $group): bool => $group->appliesTo($scope))
            ->keyBy('id');

        if ($groups->isEmpty()) {
            return [];
        }

        $selected = $this->explicitSelections($workorder, $groups);
        $selected = $this->inferSelectionsFromTdrs($workorder, $groups, $selected);
        $coverage = $this->bushingCoverageFromLines($workorder, $groups);

        foreach ($selected as $groupId => $selection) {
            /** @var ManualPartGroup|null $group */
            $group = $groups->get((int) $groupId);
            if (! $group) {
                continue;
            }

            /** @var ManualPartGroupOption|null $option */
            $option = $group->options->firstWhere('id', (int) $selection['option_id']);
            if (! $option) {
                continue;
            }

            // Bushing groups are quantity pools, not choose-one groups. Their
            // selected sizes are resolved together from WoBushingLine below.
            if ($group->type === ManualPartGroup::TYPE_OVERSIZE) {
                continue;
            }

            $selectionQty = max(1, (int) $selection['qty']);
            $reason = $this->coverageReason($group, $option);

            if ($group->behavior === ManualPartGroup::BEHAVIOR_CHOOSE_ONE) {
                foreach ($group->options as $candidate) {
                    $componentId = (int) ($candidate->component_id ?? 0);
                    if ($componentId <= 0 || $candidate->id === $option->id) {
                        continue;
                    }

                    $this->addCoverage($coverage, $componentId, PHP_INT_MAX, $reason, $group, $option);
                }

                continue;
            }

            foreach ($option->coverages as $member) {
                $this->expandBundleMember(
                    $coverage,
                    $member,
                    $selectionQty,
                    $scope,
                    $reason,
                    $group,
                    $option
                );
            }
        }

        return $coverage;
    }

    /** @param Collection<int, ManualPartGroup> $groups */
    private function explicitSelections(Workorder $workorder, Collection $groups): array
    {
        return WorkorderPartGroupSelection::query()
            ->where('workorder_id', $workorder->id)
            ->whereIn('manual_part_group_id', $groups->keys()->all())
            ->get()
            ->mapWithKeys(fn (WorkorderPartGroupSelection $selection): array => [
                (int) $selection->manual_part_group_id => [
                    'option_id' => (int) $selection->manual_part_group_option_id,
                    'qty' => max(1, (int) $selection->qty),
                ],
            ])
            ->all();
    }

    /**
     * Legacy-compatible inference: an existing TDR selection of an ASSY row or
     * an option component activates the matching group without rewriting history.
     *
     * @param Collection<int, ManualPartGroup> $groups
     */
    private function inferSelectionsFromTdrs(Workorder $workorder, Collection $groups, array $selected): array
    {
        $assemblyOptionByLegacyId = [];
        $optionByComponentId = [];

        foreach ($groups as $group) {
            foreach ($group->options as $option) {
                if ($group->behavior === ManualPartGroup::BEHAVIOR_CHOOSE_ONE
                    && $group->type !== ManualPartGroup::TYPE_OVERSIZE
                    && (int) ($option->component_id ?? 0) > 0) {
                    $optionByComponentId[(int) $option->component_id][] = [$group, $option];
                }
                foreach ($option->coverages as $coverage) {
                    if ((int) ($coverage->legacy_component_assembly_id ?? 0) > 0) {
                        $assemblyOptionByLegacyId[(int) $coverage->legacy_component_assembly_id] = [$group, $option];
                    }
                }
            }
        }

        Tdr::query()
            ->where('workorder_id', $workorder->id)
            ->where(function ($query): void {
                $query->whereNotNull('order_component_id')
                    ->orWhereNotNull('order_component_assembly_id');
            })
            ->get(['order_component_id', 'order_component_assembly_id', 'qty'])
            ->each(function (Tdr $tdr) use (&$selected, $assemblyOptionByLegacyId, $optionByComponentId): void {
                $matches = [];
                $assemblyId = (int) ($tdr->order_component_assembly_id ?? 0);
                if ($assemblyId > 0 && isset($assemblyOptionByLegacyId[$assemblyId])) {
                    $matches[] = $assemblyOptionByLegacyId[$assemblyId];
                }

                $componentId = (int) ($tdr->order_component_id ?? 0);
                foreach ($optionByComponentId[$componentId] ?? [] as $match) {
                    $matches[] = $match;
                }

                foreach ($matches as [$group, $option]) {
                    if (isset($selected[$group->id])) {
                        continue;
                    }
                    $selected[(int) $group->id] = [
                        'option_id' => (int) $option->id,
                        'qty' => max(1, (int) ($tdr->qty ?? 1)),
                    ];
                }
            });

        return $selected;
    }

    /** @param Collection<int, ManualPartGroup> $groups */
    private function bushingCoverageFromLines(Workorder $workorder, Collection $groups): array
    {
        $coverage = [];
        $optionsByComponentId = [];
        foreach ($groups->where('type', ManualPartGroup::TYPE_OVERSIZE) as $group) {
            foreach ($group->options as $option) {
                $componentId = (int) ($option->component_id ?? 0);
                if ($componentId > 0) {
                    $optionsByComponentId[$componentId][] = [$group, $option];
                }
            }
        }

        if ($optionsByComponentId === []) {
            return $coverage;
        }

        $selectedComponentIdsByGroup = [];
        WoBushingLine::query()
            ->where(function ($query) use ($workorder): void {
                $query->where('workorder_id', $workorder->id)
                    ->orWhereHas('woBushing', fn ($woBushing) => $woBushing->where('workorder_id', $workorder->id));
            })
            ->where('do_not_order', false)
            ->whereIn('component_id', array_keys($optionsByComponentId))
            ->get(['component_id', 'qty'])
            ->each(function (WoBushingLine $line) use (&$selectedComponentIdsByGroup, $optionsByComponentId): void {
                foreach ($optionsByComponentId[(int) $line->component_id] ?? [] as [$group, $option]) {
                    $selectedComponentIdsByGroup[(int) $group->id][(int) $option->component_id] = true;
                }
            });

        foreach ($groups->where('type', ManualPartGroup::TYPE_OVERSIZE) as $group) {
            $selectedComponentIds = $selectedComponentIdsByGroup[(int) $group->id] ?? [];
            if ($selectedComponentIds === []) {
                continue;
            }

            $selectedOptions = $group->options
                ->filter(fn (ManualPartGroupOption $option): bool => isset($selectedComponentIds[(int) $option->component_id]));
            $referenceOption = $selectedOptions->first();
            if (! $referenceOption) {
                continue;
            }

            $selectedPartNumbers = $selectedOptions
                ->pluck('part_number')
                ->map(fn ($partNumber): string => trim((string) $partNumber))
                ->filter()
                ->unique()
                ->implode(', ');
            $reason = trim('Bushing size not selected; ordered P/N '.$selectedPartNumbers);

            foreach ($group->options as $candidate) {
                $componentId = (int) ($candidate->component_id ?? 0);
                if ($componentId <= 0 || isset($selectedComponentIds[$componentId])) {
                    continue;
                }

                $this->addCoverage($coverage, $componentId, PHP_INT_MAX, $reason, $group, $referenceOption);
            }
        }

        return $coverage;
    }

    private function expandBundleMember(
        array &$coverage,
        $member,
        int $parentQty,
        string $scope,
        string $reason,
        ManualPartGroup $selectedGroup,
        ManualPartGroupOption $selectedOption
    ): void {
        if (! $member->appliesTo($scope)) {
            return;
        }

        $memberQty = max(1, (int) $member->qty) * max(1, $parentQty);
        $componentId = (int) ($member->component_id ?? 0);
        if ($componentId > 0) {
            $this->addCoverage(
                $coverage,
                $componentId,
                $memberQty,
                $reason,
                $selectedGroup,
                $selectedOption
            );

            return;
        }

        $coveredOption = $member->coveredOption;
        if (! $coveredOption || $coveredOption->group?->type !== ManualPartGroup::TYPE_ASSY) {
            return;
        }

        foreach ($coveredOption->coverages as $nestedMember) {
            $this->expandBundleMember(
                $coverage,
                $nestedMember,
                $memberQty,
                $scope,
                $reason,
                $selectedGroup,
                $selectedOption
            );
        }
    }

    private function coverageReason(ManualPartGroup $group, ManualPartGroupOption $option): string
    {
        $partNumber = trim((string) $option->part_number);

        if ($group->type === ManualPartGroup::TYPE_KIT) {
            return trim('Included in KIT '.$partNumber);
        }

        if ($group->type === ManualPartGroup::TYPE_ASSY) {
            return trim('Included in ASSY '.$partNumber);
        }

        return trim('Alternative not selected; selected P/N '.$partNumber);
    }

    private function addCoverage(
        array &$coverage,
        int $componentId,
        int $qty,
        string $reason,
        ManualPartGroup $group,
        ManualPartGroupOption $option
    ): void {
        if (! isset($coverage[$componentId])) {
            $coverage[$componentId] = [
                'covered_qty' => 0,
                'reason' => $reason,
                'group_id' => (int) $group->id,
                'option_id' => (int) $option->id,
            ];
        }

        $coverage[$componentId]['covered_qty'] = $qty === PHP_INT_MAX
            ? PHP_INT_MAX
            : min(PHP_INT_MAX, $coverage[$componentId]['covered_qty'] + $qty);
    }
}
