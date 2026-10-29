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
    private ?Workorder $snapshotWorkorder = null;
    private array $snapshotValues = [];

    /** A disposable, read-only calculation; never bind this instance as a singleton. */
    public function forSnapshot(Workorder $workorder): self
    {
        $snapshot = clone $this;
        $snapshot->snapshotWorkorder = $workorder;
        $snapshot->snapshotValues = [];
        return $snapshot;
    }

    private function snapshotValue(Workorder $workorder, string $key, callable $calculate)
    {
        if ($this->snapshotWorkorder !== $workorder) return $calculate();
        if (!array_key_exists($key, $this->snapshotValues)) {
            $this->snapshotValues[$key] = $calculate();
        }
        return $this->snapshotValues[$key];
    }

    /**
     * KIT supply excludes a bundle's own head and shares nested bushing supply
     * across ordered sizes. Other forms retain the full composition.
     * @return array<int, array{covered_qty:int, reason:string, group_id:int, option_id:int}>
     */
    public function coverageForWorkorder(Workorder $workorder, string $scope, bool $excludeSelectedHead = false, bool $includeBushingChoices = true): array
    {
        return $this->snapshotValue($workorder, 'coverage:'.json_encode([$scope, $excludeSelectedHead, $includeBushingChoices]),
            fn () => $this->calculateCoverage($workorder, $scope, $excludeSelectedHead, $includeBushingChoices));
    }

    private function calculateCoverage(Workorder $workorder, string $scope, bool $excludeSelectedHead, bool $includeBushingChoices): array
    {
        if (! in_array($scope, ManualPartGroup::validScopes(), true)) {
            throw new \InvalidArgumentException("Invalid part group scope: {$scope}");
        }

        $allGroups = $this->snapshotValue($workorder, 'groups', fn () => ManualPartGroup::query()
            ->whereIn('manual_id', $workorder->usedManualIds())
            ->with([
                'options.coverages',
                'serviceBulletin:id,ac_mfg_service_bulletin_no,oem_service_bulletin_no',
            ])
            ->get()
            ->keyBy('id'));
        $groups = $allGroups->filter(fn (ManualPartGroup $group): bool => $group->appliesTo($scope));

        $automaticCoverage = $this->snapshotValue($workorder, 'automatic', fn () => $this->automaticIplCoverage($workorder));
        if ($groups->isEmpty()) {
            return $automaticCoverage;
        }

        $optionsById = $allGroups
            ->flatMap(fn (ManualPartGroup $group) => $group->options)
            ->keyBy('id');

        $selected = $this->explicitSelections($workorder, $groups);
        $selected = $this->inferSelectionsFromTdrs($workorder, $groups, $selected);
        $coverage = ($includeBushingChoices ? $this->bushingCoverageFromLines($workorder, $groups) : []) + $automaticCoverage;
        $iplFamilies = $this->snapshotValue($workorder, 'families', fn () => $this->bundleIplFamilies($workorder, $allGroups));
        $configuredMembers = $this->snapshotValue($workorder, 'configuration', fn () => app(WorkorderAssyConfiguration::class)->selectedCoverageIds($workorder));
        $selectedBushingQuantities = $excludeSelectedHead
            ? WoBushingLine::query()->where(function ($query) use ($workorder): void {
                $query->where('workorder_id', $workorder->id)
                    ->orWhereHas('woBushing', fn ($bushing) => $bushing->where('workorder_id', $workorder->id));
            })->where('do_not_order', false)->get()->groupBy('component_id')
                ->map(fn ($lines): int => $lines->sum(fn ($line): int => max(1, (int) $line->qty)))->all()
            : [];

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

            foreach ($this->bundleMembers($option, $scope, $iplFamilies, $configuredMembers) as $member) {
                $this->expandBundleMember(
                    $coverage,
                    $member,
                    $selectionQty,
                    $scope,
                    $reason,
                    $group,
                    $option,
                    $allGroups,
                    $optionsById,
                    [(int) $option->id => true],
                    $iplFamilies,
                    $configuredMembers,
                    $excludeSelectedHead,
                    $selectedBushingQuantities
                );
            }
        }

        return $this->excludeBundleIplAlternatives($workorder, $groups, $coverage);
    }

    /**
     * Receiving a part through an ordered bundle is also an order of its IPL
     * position. Exact composition flags restrict supplied P/Ns, not exclusion
     * of unselected letter variants in the work lists.
     */
    private function excludeBundleIplAlternatives(Workorder $workorder, Collection $groups, array $coverage): array
    {
        $supplied = collect($coverage)->filter(fn (array $entry): bool =>
            $entry['covered_qty'] > 0 && $entry['covered_qty'] < PHP_INT_MAX
            && $groups->get((int) $entry['group_id'])?->behavior === ManualPartGroup::BEHAVIOR_BUNDLE
        );
        if ($supplied->isEmpty()) {
            return $coverage;
        }

        // Bushings have a separate Original/Oversize quantity-pool rule.
        $bushingIds = $groups->where('type', ManualPartGroup::TYPE_OVERSIZE)
            ->flatMap(fn ($group) => $group->options->pluck('component_id'))->filter();
        $parts = \App\Models\Component::whereIn('manual_id', $workorder->usedManualIds())
            ->whereNotIn('id', \App\Models\Component::query()->bushingCandidates()->select('components.id'))
            ->whereNotIn('id', $bushingIds)
            ->get(['id', 'manual_id', 'ipl_num']);
        $orderedIds = Tdr::where('workorder_id', $workorder->id)->whereNotNull('order_component_id')
            ->pluck('order_component_id')->map(fn ($id) => (int) $id)->all();

        foreach ($parts->groupBy(function ($part): string {
            $ipl = PartVariantGrouping::iplFamily((string) $part->ipl_num);
            return $part->manual_id.'|'.($ipl !== '' ? $ipl : 'component-'.$part->id);
        }) as $family) {
            $member = $family->first(fn ($part): bool => $supplied->has((int) $part->id));
            if (! $member) {
                continue;
            }
            foreach ($family as $variant) {
                $id = (int) $variant->id;
                if (isset($coverage[$id]) || in_array($id, $orderedIds, true)) {
                    continue;
                }
                $coverage[$id] = array_replace($supplied->get((int) $member->id), [
                    'covered_qty' => PHP_INT_MAX,
                    'reason' => 'IPL alternative not selected; '.$supplied->get((int) $member->id)['reason'],
                ]);
            }
        }

        return $coverage;
    }

    /** Letter suffixes are variants of one IPL position, without a manual group. */
    private function automaticIplCoverage(Workorder $workorder): array
    {
        $orderedIds = Tdr::where('workorder_id', $workorder->id)->whereNotNull('order_component_id')
            ->pluck('order_component_id')->map(fn ($id) => (int) $id)->all();
        $logCardData = \App\Models\LogCard::where('workorder_id', $workorder->id)->value('component_data');
        $logCardRows = is_string($logCardData) ? json_decode($logCardData, true) : $logCardData;
        $logCardIds = collect(is_array($logCardRows) ? $logCardRows : [])
            ->filter(fn ($row): bool => is_array($row) && ! empty($row['included']) && ! empty($row['component_id']))
            ->pluck('component_id')->map(fn ($id) => (int) $id)->unique()->all();
        if ($orderedIds === [] && $logCardIds === []) {
            return [];
        }
        $parts = \App\Models\Component::whereIn('manual_id', $workorder->usedManualIds())
            ->whereNotIn('id', \App\Models\Component::query()->bushingCandidates()->select('components.id'))
            ->get(['id', 'manual_id', 'ipl_num', 'part_number']);
        $coverage = [];
        foreach ($parts->groupBy(fn ($p) => $p->manual_id.'|'.PartVariantGrouping::iplFamily($p->ipl_num)) as $family) {
            $selected = $family->whereIn('id', $orderedIds);
            // A replacement ordered in TDR takes precedence over the received
            // Log Card variant. Otherwise use the actual installed variant.
            if ($selected->isEmpty()) {
                $selected = $family->whereIn('id', $logCardIds);
            }
            if ($selected->isEmpty()) {
                continue;
            }
            foreach ($family->whereNotIn('id', $selected->pluck('id')->all()) as $part) {
                $coverage[(int) $part->id] = [
                    'covered_qty' => PHP_INT_MAX, 'group_id' => 0, 'option_id' => 0,
                    'reason' => 'IPL alternative not selected; selected P/N: '.$selected->pluck('part_number')->implode(', '),
                ];
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
     * Ordering an option component in TDR activates its group for that WO.
     * Legacy component-assembly links remain supported without rewriting history.
     *
     * @param Collection<int, ManualPartGroup> $groups
     */
    private function inferSelectionsFromTdrs(Workorder $workorder, Collection $groups, array $selected): array
    {
        $assemblyOptionByLegacyId = [];
        $optionByComponentId = [];

        foreach ($groups as $group) {
            foreach ($group->options as $option) {
                if ($group->type !== ManualPartGroup::TYPE_OVERSIZE
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

    /** Ordinary letter variants share a position, never a P/N or another manual. */
    private function bundleIplFamilies(Workorder $workorder, Collection $groups): array
    {
        return $this->bundleIplFamiliesForManuals($workorder->usedManualIds(), $groups);
    }

    /** Shared by coverage and Log Card composition; explicit subsets stay restricted. */
    public function bundleIplFamiliesForManuals(array $manualIds, Collection $groups): array
    {
        $bushingIds = $groups->where('type', ManualPartGroup::TYPE_OVERSIZE)
            ->flatMap(fn ($group) => $group->options->pluck('component_id'))->filter()->all();
        $parts = \App\Models\Component::whereIn('manual_id', $manualIds)
            ->where('is_bush', false)->whereNotIn('id', $bushingIds)
            ->get(['id', 'manual_id', 'ipl_num']);
        $explicitAlternatives = $groups->where('type', ManualPartGroup::TYPE_ALTERNATIVE)
            ->map(fn ($group) => $group->options->pluck('component_id')->filter()->map(fn ($id) => (int) $id)->all());
        $families = [];
        foreach ($parts->groupBy(function ($part): string {
            $ipl = PartVariantGrouping::iplFamily((string) $part->ipl_num);
            return $part->manual_id.'|'.($ipl !== '' ? $ipl : 'component-'.$part->id);
        }) as $family) {
            $ids = $family->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            // A deliberately restricted subset (e.g. A/B versus C/D) is not
            // permission to cross-cover the other configuration's variants.
            $restricted = $explicitAlternatives->contains(function ($optionIds) use ($ids): bool {
                $overlap = array_intersect($ids, $optionIds);
                return $overlap !== [] && count($overlap) < count($ids);
            });
            foreach ($ids as $id) {
                $families[$id] = $restricted ? [$id] : $ids;
            }
        }
        return $families;
    }

    /** Count directly listed letter variants once per assembly, not once per P/N.
     * Separate nested assembly occurrences remain additive.
     */
    private function bundleMembers(ManualPartGroupOption $option, string $scope, array $families, array $configuredMembers): Collection
    {
        return app(WorkorderAssyConfiguration::class)->members($option, $configuredMembers, $scope)
            ->groupBy(function ($member) use ($families): string {
                $id = (int) $member->component_id;
                return $member->expandsIplFamily() && isset($families[$id])
                    ? 'ipl:'.$families[$id][0] : 'member:'.$member->id;
            })
            ->map(fn ($members) => $members->sortByDesc('qty')->first())->values();
    }

    private function expandBundleMember(
        array &$coverage,
        $member,
        int $parentQty,
        string $scope,
        string $reason,
        ManualPartGroup $selectedGroup,
        ManualPartGroupOption $selectedOption,
        Collection $groups,
        Collection $optionsById,
        array $optionPath,
        array $iplFamilies,
        array $configuredMembers,
        bool $excludeSelectedHead = false,
        array $selectedBushingQuantities = []
    ): void {
        if (! $member->appliesTo($scope)) {
            return;
        }

        $memberQty = max(1, (int) $member->qty) * max(1, $parentQty);
        $componentId = (int) ($member->component_id ?? 0);
        if ($componentId > 0) {
            // The head of a nested ASSY is the chosen physical P/N, not the
            // entire letter family of other possible child assemblies.
            $owningOption = $optionsById->get((int) $member->manual_part_group_option_id);
            $isAssyHead = $componentId === (int) ($owningOption?->component_id ?? 0)
                && $groups->get((int) ($owningOption?->manual_part_group_id ?? 0))?->type === ManualPartGroup::TYPE_ASSY;
            $memberIds = $member->expandsIplFamily() && ! $isAssyHead
                ? ($iplFamilies[$componentId] ?? [$componentId]) : [$componentId];
            foreach ($memberIds as $variantId) {
                // A selected bundle is the supply, not a duplicate of itself.
                // Do not erase coverage from a different/nested assembly.
                if ($excludeSelectedHead && count($optionPath) === 1
                    && $variantId === (int) $selectedOption->component_id) {
                    continue;
                }
                $this->addCoverage($coverage, $variantId, $memberQty, $reason, $selectedGroup, $selectedOption);
            }
            if ($isAssyHead) {
                foreach ($iplFamilies[$componentId] ?? [] as $variantId) {
                    if ($variantId !== $componentId) {
                        $this->addCoverage($coverage, $variantId, PHP_INT_MAX,
                            'IPL alternative not selected; included ASSY P/N '.$owningOption->part_number,
                            $selectedGroup, $selectedOption);
                    }
                }
            }

            return;
        }

        $coveredOptionId = (int) ($member->covered_manual_part_group_option_id ?? 0);
        /** @var ManualPartGroupOption|null $coveredOption */
        $coveredOption = $optionsById->get($coveredOptionId);
        if (! $coveredOption || isset($optionPath[$coveredOptionId])) {
            return;
        }

        /** @var ManualPartGroup|null $coveredGroup */
        $coveredGroup = $groups->get((int) $coveredOption->manual_part_group_id);
        if (! $coveredGroup) {
            return;
        }

        if ($coveredGroup->type === ManualPartGroup::TYPE_OVERSIZE) {
            $selectedSizes = $coveredGroup->options->filter(fn ($option): bool => isset($selectedBushingQuantities[(int) $option->component_id]));
            $sharedSupply = $memberQty;
            foreach ($coveredGroup->options->sortBy('component_id') as $bushingOption) {
                $bushingComponentId = (int) ($bushingOption->component_id ?? 0);
                if ($bushingComponentId <= 0) {
                    continue;
                }

                $coveredQty = $memberQty;
                if ($selectedSizes->isNotEmpty()) {
                    // One supplied family quantity is shared by the chosen sizes.
                    // Existing coverage from other bundles also consumes demand.
                    $demand = $selectedBushingQuantities[$bushingComponentId] ?? 0;
                    $remaining = max(0, $demand - ($coverage[$bushingComponentId]['covered_qty'] ?? 0));
                    $coveredQty = min($sharedSupply, $remaining);
                    $sharedSupply -= $coveredQty;
                    if ($coveredQty === 0) continue;
                }

                $this->addCoverage(
                    $coverage,
                    $bushingComponentId,
                    $coveredQty,
                    $reason,
                    $selectedGroup,
                    $selectedOption
                );
            }

            return;
        }

        if ($coveredGroup->type === ManualPartGroup::TYPE_ALTERNATIVE) {
            $coveredIds = [];
            foreach ($coveredGroup->options as $alternativeOption) {
                $alternativeComponentId = (int) ($alternativeOption->component_id ?? 0);
                if ($alternativeComponentId <= 0) {
                    continue;
                }

                foreach ($iplFamilies[$alternativeComponentId] ?? [$alternativeComponentId] as $variantId) {
                    if (! isset($coveredIds[$variantId])) {
                        $this->addCoverage($coverage, $variantId, $memberQty, $reason, $selectedGroup, $selectedOption);
                        $coveredIds[$variantId] = true;
                    }
                }
            }

            return;
        }

        if ($coveredGroup->type !== ManualPartGroup::TYPE_ASSY) {
            return;
        }

        $optionPath[$coveredOptionId] = true;

        foreach ($this->bundleMembers($coveredOption, $scope, $iplFamilies, $configuredMembers) as $nestedMember) {
            $this->expandBundleMember(
                $coverage,
                $nestedMember,
                $memberQty,
                $scope,
                $reason,
                $selectedGroup,
                $selectedOption,
                $groups,
                $optionsById,
                $optionPath,
                $iplFamilies,
                $configuredMembers,
                $excludeSelectedHead,
                $selectedBushingQuantities
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
                'assy_part_number' => $group->type === ManualPartGroup::TYPE_ASSY
                    ? (string) $option->part_number : null,
            ];
        }

        $coverage[$componentId]['covered_qty'] = $qty === PHP_INT_MAX
            || $coverage[$componentId]['covered_qty'] === PHP_INT_MAX
            ? PHP_INT_MAX
            : min(PHP_INT_MAX, $coverage[$componentId]['covered_qty'] + $qty);
    }
}
