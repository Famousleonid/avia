<?php

namespace App\Services;

use App\Models\Code;
use App\Models\Component;
use App\Models\Manual;
use App\Models\ManualPartGroup;
use App\Models\Necessary;
use App\Models\StdProcess;
use App\Models\Tdr;
use App\Models\Workorder;
use App\Models\WorkorderStdProcessItem;
use App\Services\ManualIplBranchRuleResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkorderStdProcessItemsService
{
    /**
     * Rebuild one workorder's reduced STD list from component flags minus current TDR exclusions.
     * Preview retains fully excluded rows in memory only, without changing the stored work list.
     * @return array<int, array<string, mixed>>
     */
    public function rebuild(Workorder $workorder, bool $previewExcluded = false): array
    {
        if ($workorder->relationLoaded('unit')
            && (int) ($workorder->unit?->id ?? 0) !== (int) $workorder->unit_id) {
            $workorder->unsetRelation('unit');
        }
        if ($workorder->relationLoaded('instruction')
            && (int) ($workorder->instruction?->id ?? 0) !== (int) $workorder->instruction_id) {
            $workorder->unsetRelation('instruction');
        }

        $workorder->loadMissing(['unit.manuals', 'instruction:id,name']);
        $branchResolver = app(ManualIplBranchRuleResolver::class);

        return DB::transaction(function () use ($workorder, $branchResolver, $previewExcluded): array {
            if (! $previewExcluded) {
                WorkorderStdProcessItem::query()
                    ->where('workorder_id', $workorder->id)
                    ->delete();
            }

            $manualIds = $workorder->usedManualIds();
            if ($manualIds === []) {
                return [];
            }

            $manualsById = Manual::query()
                ->whereIn('id', $manualIds)
                ->get()
                ->keyBy(fn (Manual $manual): int => (int) $manual->id);
            $excludedQtyByComponent = $this->excludedQtyByComponent($workorder);
            $excludedQtyByBaseIpl = $this->excludedQtyByBaseIpl($workorder);
            $excludedQtyByAlternative = $this->excludedQtyByAlternative($excludedQtyByComponent, $manualIds);
            $now = now();
            $insertRows = [];
            $sortOrderByStd = array_fill_keys(StdProcess::validStdValues(), 1);
            $scopeResolver = app(WorkorderPartScopeResolver::class);

            foreach ($manualIds as $manualId) {
                /** @var Manual|null $manual */
                $manual = $manualsById->get($manualId);
                if (! $manual) {
                    continue;
                }

                if (! $previewExcluded) {
                    StdProcess::syncFromComponentFlagsForManualWhenCountsDiffer($manual);
                }

                foreach (StdProcess::validStdValues() as $std) {
                    $scopeQuantities = $scopeResolver->componentQuantities($workorder, $std);
                    $scopeComponentIds = $scopeQuantities !== null ? array_keys($scopeQuantities) : null;
                    $scopeComponentRank = $scopeComponentIds !== null
                        ? array_flip($scopeComponentIds)
                        : null;
                    $legacyInferredPart = $workorder->scope_type === null
                        && $workorder->modified_scope_part_group_option_id === null
                        && $scopeQuantities !== null;
                    $manualRows = $this->manualStdRowsForManualStd($manualId, $std);
                    $flagColumn = $this->componentFlagColumnForStd($std);

                    $eligibleRows = [];

                    foreach ($manualRows as $manualRow) {
                        $component = $manualRow->component;
                        if (! $component || ! (bool) $component->{$flagColumn}) {
                            continue;
                        }

                        if ($scopeComponentIds !== null
                            && ! in_array((int) $component->id, $scopeComponentIds, true)) {
                            continue;
                        }

                        if (! $branchResolver->allowsComponentForUnit($workorder->unit, (string) ($component->ipl_num ?? ''), $manualId)) {
                            continue;
                        }

                        $rowEff = $manualRow->eff_code ?? $component->eff_code;
                        $sideExclusion = '';
                        if (! StdProcess::stdRowEffMatchesUnit($rowEff, (string) ($workorder->unit->eff_code ?? ''))) {
                            $sideExclusion = $previewExcluded && in_array($std, [StdProcess::STD_NDT, StdProcess::STD_CAD], true)
                                ? $this->oppositeSideReason($rowEff, (string) $workorder->unit->eff_code)
                                : '';
                            if ($sideExclusion === '') {
                                continue;
                            }
                        }

                        $eligibleRows[] = [
                            'manual_row' => $manualRow,
                            'component' => $component,
                            'row_eff' => $rowEff,
                            'side_exclusion_reason' => $sideExclusion,
                        ];
                    }

                    // Opposite-side preview rows must not displace a universal working row.
                    $sideRows = array_values(array_filter($eligibleRows, fn (array $row) => $row['side_exclusion_reason'] !== ''));
                    $eligibleRows = array_merge(
                        $this->preferEffSpecificVariants(array_values(array_filter($eligibleRows, fn (array $row) => $row['side_exclusion_reason'] === ''))),
                        $this->preferEffSpecificVariants($sideRows)
                    );
                    if ($legacyInferredPart && $scopeComponentRank !== null && count($eligibleRows) > 1) {
                        usort($eligibleRows, static function (array $left, array $right) use ($scopeComponentRank): int {
                            return ($scopeComponentRank[(int) $left['component']->id] ?? PHP_INT_MAX)
                                <=> ($scopeComponentRank[(int) $right['component']->id] ?? PHP_INT_MAX);
                        });
                        $eligibleRows = array_slice($eligibleRows, 0, 1);
                    }

                    foreach ($eligibleRows as $eligibleRow) {
                        /** @var StdProcess $manualRow */
                        $manualRow = $eligibleRow['manual_row'];
                        /** @var Component $component */
                        $component = $eligibleRow['component'];
                        $rowEff = $eligibleRow['row_eff'];

                        // When the WO head unit is itself a Manual Part, the received item is
                        // one detached part, not the manual's assembly quantity.
                        $baseQty = $scopeQuantities !== null
                            ? max(1, (int) ($scopeQuantities[(int) $component->id] ?? 1))
                            : $this->baseQty($component, $manualRow);
                        $excludedSourceQty = (int) ($excludedQtyByComponent[$component->id] ?? 0);
                        $excludedSourceQty = max($excludedSourceQty, $excludedQtyByAlternative[$std][$component->id] ?? 0);

                        foreach ($this->baseIplKeys((string) ($component->ipl_num ?? '')) as $baseKey) {
                            $manualBaseKey = $this->manualBaseIplKey($manualId, $baseKey);
                            $excludedSourceQty = max($excludedSourceQty, (int) ($excludedQtyByBaseIpl[$manualBaseKey] ?? 0));
                        }

                        $excludedQty = min($baseQty, $excludedSourceQty);
                        $remainingQty = $baseQty - $excludedQty;
                        if ($eligibleRow['side_exclusion_reason'] !== '') {
                            $remainingQty = 0;
                        }

                        if ($remainingQty <= 0 && ! $previewExcluded) {
                            continue;
                        }

                        $insertRows[] = [
                            'workorder_id' => $workorder->id,
                            'manual_id' => $manualId,
                            'component_id' => $component->id,
                            'std_process_id' => $manualRow?->id,
                            'std_type' => $std,
                            'ipl_num' => (string) ($component->ipl_num ?? ''),
                            'part_number' => (string) ($component->part_number ?? ''),
                            'description' => (string) ($component->name ?? ''),
                            'process' => (string) $manualRow->process,
                            'base_qty' => $baseQty,
                            'excluded_qty' => $excludedQty,
                            'remaining_qty' => $remainingQty,
                            'manual' => (string) ($component->manual?->number ?? $manual->number ?? ''),
                            'eff_code' => StdProcess::normalizeEffCodeForStorage($rowEff),
                            'sort_order' => $sortOrderByStd[$std]++,
                            'created_at' => $now,
                            'updated_at' => $now,
                            ...($previewExcluded ? ['side_exclusion_reason' => $eligibleRow['side_exclusion_reason']] : []),
                        ];
                    }
                }
            }

            foreach ($previewExcluded ? [] : array_chunk($insertRows, 500) as $chunk) {
                WorkorderStdProcessItem::query()->insert($chunk);
            }
            return $insertRows;
        });
    }

    /**
     * Existing snapshots created before the unit-part rule self-heal on their
     * next read, without forcing every STD list to rebuild on every request.
     *
     */
    protected function scopedSnapshotNeedsRebuild(Workorder $workorder, bool $singlePerStd): bool
    {
        $rows = WorkorderStdProcessItem::query()
            ->where('workorder_id', $workorder->id)
            ->get(['component_id', 'std_type', 'base_qty']);
        $resolver = app(WorkorderPartScopeResolver::class);

        if ($rows->contains(function (WorkorderStdProcessItem $item) use ($resolver, $workorder): bool {
            $scopeQuantities = $resolver->componentQuantities($workorder, (string) $item->std_type);
            $componentId = (int) $item->component_id;

            return $scopeQuantities !== null && (
                ! isset($scopeQuantities[$componentId])
                || (int) $item->base_qty !== max(1, (int) $scopeQuantities[$componentId])
            );
        })) {
            return true;
        }

        return $singlePerStd && $rows
            ->groupBy('std_type')
            ->contains(static fn (Collection $stdRows): bool => $stdRows->count() > 1);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function snapshotRowsForWorkorder(Workorder $workorder, string $std, bool $includeExcluded = false): array
    {
        StdProcess::assertValidStd($std);

        $hasRows = $this->hasRowsForWorkorder((int) $workorder->id);
        $scopeQuantities = $hasRows
            ? app(WorkorderPartScopeResolver::class)->componentQuantities($workorder)
            : null;
        $scopedSnapshotNeedsRebuild = $scopeQuantities !== null
            && $this->scopedSnapshotNeedsRebuild(
                $workorder,
                $workorder->scope_type === null
                    && $workorder->modified_scope_part_group_option_id === null
            );

        if (! $hasRows || $scopedSnapshotNeedsRebuild) {
            $this->rebuild($workorder);
        }

        $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($workorder, $std);
        $items = $includeExcluded
            ? collect($this->rebuild($workorder, true))->where('std_type', $std)
                ->map(fn (array $row) => (new WorkorderStdProcessItem())->forceFill($row))
            : WorkorderStdProcessItem::query()
            ->where('workorder_id', $workorder->id)
            ->where('std_type', $std)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $rows = $items
            ->map(function (WorkorderStdProcessItem $item) use ($coverage): array {
                $row = $item->toSnapshotRow();
                $covered = $coverage[(int) $item->component_id] ?? null;
                if ($item->side_exclusion_reason) {
                    $row['group_required_qty'] = (int) $item->base_qty;
                    $row['group_crossed_out'] = true;
                    $row['side_excluded'] = true;
                    $row['group_crossout_reason'] = $item->side_exclusion_reason;
                    $row['qty'] = 0;
                    return $row;
                }
                if ((int) $item->remaining_qty <= 0) {
                    $row['group_required_qty'] = (int) $item->base_qty;
                    $row['group_crossed_out'] = true;
                    $row['group_crossout_reason'] = 'TDR: Repair / Missing / Order New';
                    $row['qty'] = 0;
                    // TDR exclusions can cover an entire IPL family. Do not bring
                    // back its unselected alternatives just because they also have zero remaining QTY.
                    $row['tdr_excluded'] = ! $covered || (int) $covered['covered_qty'] < (int) $item->base_qty;
                    return $row;
                }
                if (! $covered) {
                    return $row;
                }

                $requiredQty = max(1, (int) $row['qty']);
                $coveredQty = min($requiredQty, (int) $covered['covered_qty']);
                $row['group_required_qty'] = $requiredQty;
                $row['group_covered_qty'] = $coveredQty;
                $row['group_remaining_qty'] = max(0, $requiredQty - $coveredQty);
                $row['group_crossed_out'] = $coveredQty >= $requiredQty;
                $row['group_crossout_reason'] = (string) $covered['reason'];
                $row['qty'] = $row['group_crossed_out'] ? 0 : $row['group_remaining_qty'];

                return $row;
            })
            // Process sheets contain work to perform, not rejected alternatives.
            // Filter before grouping/pagination; KIT keeps its own crossout display.
            ->reject(fn (array $row): bool => ! empty($row['group_crossed_out'])
                && (! $includeExcluded || (empty($row['tdr_excluded']) && empty($row['side_excluded']))))
            ->values()
            ->all();

        return StdProcess::sortRowsForSnapshot(app(PartVariantGrouping::class)->annotateStdRows($rows, $std));
    }

    public function hasRowsForWorkorder(int $workorderId): bool
    {
        return WorkorderStdProcessItem::query()
            ->where('workorder_id', $workorderId)
            ->exists();
    }

    private function oppositeSideReason(?string $rowEff, string $unitEff): string
    {
        $side = static function (?string $eff): ?string {
            $tokens = array_unique(array_map(static fn ($token) => match (strtoupper($token)) {
                'L', 'LH' => 'LH',
                'R', 'RH' => 'RH',
                default => '',
            }, StdProcess::effCodeTokens($eff)));
            return count($tokens) === 1 && $tokens[0] !== '' ? $tokens[0] : null;
        };
        $unitSide = $side($unitEff);
        $rowSide = $side($rowEff);
        return $unitSide && $rowSide && $unitSide !== $rowSide
            ? $rowSide.' part excluded: '.$unitSide.' unit'
            : '';
    }

    public function invalidateForManual(int $manualId): void
    {
        if ($manualId <= 0) {
            return;
        }

        WorkorderStdProcessItem::query()
            ->whereIn('workorder_id', Workorder::query()
                ->select('workorders.id')
                ->join('units', 'units.id', '=', 'workorders.unit_id')
                ->join('manuals as primary_manuals', 'primary_manuals.id', '=', 'units.manual_id')
                ->where(function ($query) use ($manualId): void {
                    $query->where('units.manual_id', $manualId)
                        ->orWhereJsonContains('primary_manuals.additional_manual_ids', $manualId);
                }))
            ->delete();
    }

    /**
     * @return array<string, array<int, int>>
     */
    protected function excludedQtyByAlternative(array $excludedQtyByComponent, array $manualIds): array
    {
        // Reuse the same Repair / Missing / Order New exclusions, counted once per TDR row.
        if ($excludedQtyByComponent === []) {
            return [];
        }

        $result = [];
        $groups = ManualPartGroup::query()->whereIn('manual_id', $manualIds)
            ->where('type', ManualPartGroup::TYPE_ALTERNATIVE)
            ->where('behavior', ManualPartGroup::BEHAVIOR_CHOOSE_ONE)
            ->whereHas('options', fn ($query) => $query->whereIn('component_id', array_keys($excludedQtyByComponent)))
            ->with('options.component:id,manual_id')->get();
        foreach ($groups as $group) {
            $ids = $group->options->filter(fn ($option): bool => $option->component
                && (int) $option->component->manual_id === (int) $group->manual_id)
                ->pluck('component_id')->unique();
            $qty = $ids->sum(fn ($id): int => (int) ($excludedQtyByComponent[$id] ?? 0));
            foreach (StdProcess::validStdValues() as $std) {
                if (! $group->appliesTo($std)) {
                    continue;
                }
                foreach ($ids as $id) {
                    // The same exclusion also matches its component/IPL; never subtract twice.
                    $result[$std][$id] = max($result[$std][$id] ?? 0, $qty);
                }
            }
        }

        return $result;
    }

    protected function excludedQtyByComponent(Workorder $workorder): array
    {
        $repairNecessaryIds = Necessary::query()
            ->whereRaw('LOWER(name) = ?', ['repair'])
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $missingNecessaryIds = Necessary::query()
            ->whereRaw('LOWER(name) = ?', ['missing'])
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $orderNewNecessaryIds = Necessary::query()
            ->whereRaw('LOWER(name) = ?', ['order new'])
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $repairCodeIds = Code::query()
            ->whereRaw('LOWER(name) = ?', ['repair'])
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $missingCodeIds = Code::query()
            ->whereRaw('LOWER(name) = ?', ['missing'])
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($repairNecessaryIds === [] && $missingNecessaryIds === [] && $orderNewNecessaryIds === [] && $repairCodeIds === [] && $missingCodeIds === []) {
            return [];
        }

        $excluded = [];
        Tdr::query()
            ->where('workorder_id', $workorder->id)
            ->whereNotNull('component_id')
            ->where(function ($query) use ($repairNecessaryIds, $missingNecessaryIds, $orderNewNecessaryIds, $repairCodeIds, $missingCodeIds): void {
                if ($repairNecessaryIds !== []) {
                    $query->orWhereIn('necessaries_id', $repairNecessaryIds);
                }
                if ($missingNecessaryIds !== []) {
                    $query->orWhereIn('necessaries_id', $missingNecessaryIds);
                }
                if ($orderNewNecessaryIds !== []) {
                    $query->orWhereIn('necessaries_id', $orderNewNecessaryIds);
                }
                if ($repairCodeIds !== []) {
                    $query->orWhereIn('codes_id', $repairCodeIds);
                }
                if ($missingCodeIds !== []) {
                    $query->orWhereIn('codes_id', $missingCodeIds);
                }
            })
            ->get(['component_id', 'qty'])
            ->each(function (Tdr $tdr) use (&$excluded): void {
                $componentId = (int) $tdr->component_id;
                $qty = max(1, (int) ($tdr->qty ?? 1));
                $excluded[$componentId] = ($excluded[$componentId] ?? 0) + $qty;
            });

        return $excluded;
    }

    /**
     * @return array<string, int>
     */
    protected function excludedQtyByBaseIpl(Workorder $workorder): array
    {
        $repairNecessaryIds = Necessary::query()
            ->whereRaw('LOWER(name) = ?', ['repair'])
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $missingNecessaryIds = Necessary::query()
            ->whereRaw('LOWER(name) = ?', ['missing'])
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $orderNewNecessaryIds = Necessary::query()
            ->whereRaw('LOWER(name) = ?', ['order new'])
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $repairCodeIds = Code::query()
            ->whereRaw('LOWER(name) = ?', ['repair'])
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $missingCodeIds = Code::query()
            ->whereRaw('LOWER(name) = ?', ['missing'])
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($repairNecessaryIds === [] && $missingNecessaryIds === [] && $orderNewNecessaryIds === [] && $repairCodeIds === [] && $missingCodeIds === []) {
            return [];
        }

        $excluded = [];
        Tdr::query()
            ->where('workorder_id', $workorder->id)
            ->whereNotNull('component_id')
            ->where(function ($query) use ($repairNecessaryIds, $missingNecessaryIds, $orderNewNecessaryIds, $repairCodeIds, $missingCodeIds): void {
                if ($repairNecessaryIds !== []) {
                    $query->orWhereIn('necessaries_id', $repairNecessaryIds);
                }
                if ($missingNecessaryIds !== []) {
                    $query->orWhereIn('necessaries_id', $missingNecessaryIds);
                }
                if ($orderNewNecessaryIds !== []) {
                    $query->orWhereIn('necessaries_id', $orderNewNecessaryIds);
                }
                if ($repairCodeIds !== []) {
                    $query->orWhereIn('codes_id', $repairCodeIds);
                }
                if ($missingCodeIds !== []) {
                    $query->orWhereIn('codes_id', $missingCodeIds);
                }
            })
            ->with('component:id,manual_id,ipl_num')
            ->get(['component_id', 'qty'])
            ->each(function (Tdr $tdr) use (&$excluded): void {
                if (! $tdr->component) {
                    return;
                }

                $qty = max(1, (int) ($tdr->qty ?? 1));
                $manualId = (int) ($tdr->component->manual_id ?? 0);
                if ($manualId <= 0) {
                    return;
                }

                foreach ($this->baseIplKeys((string) ($tdr->component->ipl_num ?? '')) as $baseKey) {
                    $manualBaseKey = $this->manualBaseIplKey($manualId, $baseKey);
                    $excluded[$manualBaseKey] = ($excluded[$manualBaseKey] ?? 0) + $qty;
                }
            });

        return $excluded;
    }

    /**
     * @return Collection<int, StdProcess>
     */
    protected function manualStdRowsForManualStd(int $manualId, string $std): Collection
    {
        return StdProcess::query()
            ->where('manual_id', $manualId)
            ->where('std', $std)
            ->whereNotNull('component_id')
            ->with('component.manual:id,number')
            ->orderBy('id')
            ->get();
    }

    /**
     * A universal multiline row is a fallback for an IPL variant group. When an
     * EFF-specific row from the same manual and process matches the Unit, keep the
     * specific row only so the same physical position is not emitted twice.
     *
     * @param  array<int, array{manual_row: StdProcess, component: Component, row_eff: mixed}>  $rows
     * @return array<int, array{manual_row: StdProcess, component: Component, row_eff: mixed}>
     */
    protected function preferEffSpecificVariants(array $rows): array
    {
        $specificKeys = [];

        foreach ($rows as $row) {
            if (StdProcess::normalizeEffCodeForStorage($row['row_eff']) === null) {
                continue;
            }

            $process = trim((string) $row['manual_row']->process);
            foreach ($this->baseIplKeys((string) ($row['component']->ipl_num ?? '')) as $baseIpl) {
                $specificKeys[$baseIpl . '|' . $process] = true;
            }
        }

        if ($specificKeys === []) {
            return $rows;
        }

        return array_values(array_filter($rows, function (array $row) use ($specificKeys): bool {
            if (StdProcess::normalizeEffCodeForStorage($row['row_eff']) !== null) {
                return true;
            }

            $process = trim((string) $row['manual_row']->process);
            foreach ($this->baseIplKeys((string) ($row['component']->ipl_num ?? '')) as $baseIpl) {
                if (isset($specificKeys[$baseIpl . '|' . $process])) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * @return array<int, string>
     */
    protected function baseIplKeys(string $ipl): array
    {
        $lines = preg_split('/\R+/', trim($ipl)) ?: [];
        $keys = [];

        foreach ($lines as $line) {
            $line = strtoupper(trim($line));
            if ($line === '') {
                continue;
            }

            // Repair sleeves have their own quantity; their TDR must not consume the parent part.
            if (($sleeveKey = PartVariantGrouping::repairSleeveIplKey($line)) !== null) {
                $keys[] = $sleeveKey;
                continue;
            }

            if (preg_match('/^(\d+[A-Z]*-\d+)[A-Z]+$/', $line, $matches)) {
                $keys[] = $matches[1];
                continue;
            }

            $keys[] = $line;
        }

        return array_values(array_unique($keys));
    }

    protected function manualBaseIplKey(int $manualId, string $baseIpl): string
    {
        return $manualId . '|' . strtoupper(trim($baseIpl));
    }

    protected function baseQty(Component $component, ?StdProcess $manualRow): int
    {
        $unitsAssy = max(1, (int) ($component->units_assy ?? 1));

        if (! $manualRow) {
            return $unitsAssy;
        }

        $stdQty = max(1, (int) $manualRow->qty);

        return min($stdQty, $unitsAssy);
    }

    protected function defaultProcessForStd(int $manualId, string $std): string
    {
        if ($std === StdProcess::STD_NDT) {
            return '1';
        }

        $values = StdProcess::processPicklistValuesForManual($manualId, $std);

        return (string) ($values[0] ?? '1');
    }

    protected function componentFlagColumnForStd(string $std): string
    {
        return match ($std) {
            StdProcess::STD_NDT => 'ndt_list',
            StdProcess::STD_CAD => 'cad_list',
            StdProcess::STD_STRESS => 'stress_relief_list',
            StdProcess::STD_PAINT => 'paint_list',
            default => throw new \InvalidArgumentException("Invalid std type: {$std}"),
        };
    }

}
