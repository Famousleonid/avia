<?php

namespace App\Services;

use App\Models\ManualPartGroup;
use App\Models\ManualPartGroupOption;
use App\Models\Tdr;
use App\Models\Workorder;
use App\Models\WorkorderPartGroupSelection;
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
            ->with(['options.coverages', 'serviceBulletin:id,ac_mfg_service_bulletin_no,oem_service_bulletin_no'])
            ->get()
            ->filter(fn (ManualPartGroup $group): bool => $group->appliesTo($scope))
            ->keyBy('id');

        if ($groups->isEmpty()) {
            return [];
        }

        $selected = $this->explicitSelections($workorder, $groups);
        $selected = $this->inferSelectionsFromTdrs($workorder, $groups, $selected);
        $coverage = [];

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
                if (! $member->appliesTo($scope)) {
                    continue;
                }

                $componentId = (int) $member->component_id;
                if ($componentId <= 0 || $componentId === (int) ($option->component_id ?? 0)) {
                    continue;
                }

                $this->addCoverage(
                    $coverage,
                    $componentId,
                    max(1, (int) $member->qty) * $selectionQty,
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
                if ((int) ($option->component_id ?? 0) > 0) {
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

    private function coverageReason(ManualPartGroup $group, ManualPartGroupOption $option): string
    {
        $partNumber = trim((string) $option->part_number);

        if ($group->type === ManualPartGroup::TYPE_SB_KIT) {
            $bulletin = trim((string) ($group->serviceBulletin?->oem_service_bulletin_no
                ?: $group->serviceBulletin?->ac_mfg_service_bulletin_no));

            return trim('Included in SB KIT '.$partNumber.($bulletin !== '' ? ' per '.$bulletin : ''));
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
