<?php

namespace App\Services;

use App\Models\Code;
use App\Models\Component;
use App\Models\Manual;
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
     */
    public function rebuild(Workorder $workorder): void
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

        DB::transaction(function () use ($workorder, $branchResolver): void {
            WorkorderStdProcessItem::query()
                ->where('workorder_id', $workorder->id)
                ->delete();

            $manualIds = $workorder->usedManualIds();
            if ($manualIds === []) {
                return;
            }

            $manualsById = Manual::query()
                ->whereIn('id', $manualIds)
                ->get()
                ->keyBy(fn (Manual $manual): int => (int) $manual->id);
            $excludedQtyByComponent = $this->excludedQtyByComponent($workorder);
            $excludedQtyByBaseIpl = $this->excludedQtyByBaseIpl($workorder);
            $now = now();
            $insertRows = [];
            $sortOrderByStd = array_fill_keys(StdProcess::validStdValues(), 1);
            $unitPartComponentIds = $this->overhaulUnitPartComponentIds($workorder, $branchResolver);
            $unitPartComponentRank = $unitPartComponentIds !== null
                ? array_flip($unitPartComponentIds)
                : null;

            foreach ($manualIds as $manualId) {
                /** @var Manual|null $manual */
                $manual = $manualsById->get($manualId);
                if (! $manual) {
                    continue;
                }

                StdProcess::syncFromComponentFlagsForManualWhenCountsDiffer($manual);

                foreach (StdProcess::validStdValues() as $std) {
                    $manualRows = $this->manualStdRowsForManualStd($manualId, $std);
                    $flagColumn = $this->componentFlagColumnForStd($std);

                    $eligibleRows = [];

                    foreach ($manualRows as $manualRow) {
                        $component = $manualRow->component;
                        if (! $component || ! (bool) $component->{$flagColumn}) {
                            continue;
                        }

                        if ($unitPartComponentIds !== null
                            && ! in_array((int) $component->id, $unitPartComponentIds, true)) {
                            continue;
                        }

                        if (! $branchResolver->allowsComponentForUnit($workorder->unit, (string) ($component->ipl_num ?? ''), $manualId)) {
                            continue;
                        }

                        $rowEff = $manualRow->eff_code ?? $component->eff_code;
                        if (! StdProcess::stdRowEffMatchesUnit($rowEff, (string) ($workorder->unit->eff_code ?? ''))) {
                            continue;
                        }

                        $eligibleRows[] = [
                            'manual_row' => $manualRow,
                            'component' => $component,
                            'row_eff' => $rowEff,
                        ];
                    }

                    $eligibleRows = $this->preferEffSpecificVariants($eligibleRows);
                    if ($unitPartComponentRank !== null && count($eligibleRows) > 1) {
                        usort($eligibleRows, static function (array $left, array $right) use ($unitPartComponentRank): int {
                            return ($unitPartComponentRank[(int) $left['component']->id] ?? PHP_INT_MAX)
                                <=> ($unitPartComponentRank[(int) $right['component']->id] ?? PHP_INT_MAX);
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
                        $baseQty = $unitPartComponentIds !== null
                            ? 1
                            : $this->baseQty($component, $manualRow);
                        $excludedSourceQty = (int) ($excludedQtyByComponent[$component->id] ?? 0);

                        foreach ($this->baseIplKeys((string) ($component->ipl_num ?? '')) as $baseKey) {
                            $manualBaseKey = $this->manualBaseIplKey($manualId, $baseKey);
                            $excludedSourceQty = max($excludedSourceQty, (int) ($excludedQtyByBaseIpl[$manualBaseKey] ?? 0));
                        }

                        $excludedQty = min($baseQty, $excludedSourceQty);
                        $remainingQty = $baseQty - $excludedQty;

                        if ($remainingQty <= 0) {
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
                        ];
                    }
                }
            }

            foreach (array_chunk($insertRows, 500) as $chunk) {
                WorkorderStdProcessItem::query()->insert($chunk);
            }
        });
    }

    /**
     * For an Overhaul, a Unit P/N that is also a Part of its primary Manual
     * narrows every STD list to that detached part. Returning null means the
     * normal full-manual STD list must be used.
     *
     * @return list<int>|null
     */
    protected function overhaulUnitPartComponentIds(
        Workorder $workorder,
        ManualIplBranchRuleResolver $branchResolver
    ): ?array
    {
        if (! $workorder->relationLoaded('unit')
            || (int) ($workorder->unit?->id ?? 0) !== (int) $workorder->unit_id) {
            $workorder->load('unit.manuals');
        }
        if (! $workorder->relationLoaded('instruction')
            || (int) ($workorder->instruction?->id ?? 0) !== (int) $workorder->instruction_id) {
            $workorder->load('instruction:id,name');
        }

        if (strcasecmp(trim((string) ($workorder->instruction?->name ?? '')), 'Overhaul') !== 0) {
            return null;
        }

        $unit = $workorder->unit;
        $manualId = (int) ($unit?->manual_id ?? 0);
        $unitPartNumber = $this->normalizePartNumberForComparison($unit?->part_number);

        if (! $unit || $manualId <= 0 || $unitPartNumber === '') {
            return null;
        }

        $matches = Component::query()
            ->where('manual_id', $manualId)
            ->whereNotNull('part_number')
            ->get(['id', 'manual_id', 'ipl_num', 'part_number', 'eff_code'])
            ->filter(function (Component $component) use ($unit, $manualId, $unitPartNumber, $branchResolver): bool {
                return $this->normalizePartNumberForComparison($component->part_number) === $unitPartNumber
                    && $branchResolver->allowsComponentForUnit($unit, (string) ($component->ipl_num ?? ''), $manualId)
                    && StdProcess::stdRowEffMatchesUnit(
                        $component->eff_code,
                        (string) ($unit->eff_code ?? '')
                    );
            })
            ->sort(function (Component $left, Component $right): int {
                $iplCompare = StdProcess::compareIplValues($left->ipl_num, $right->ipl_num);

                return $iplCompare !== 0
                    ? $iplCompare
                    : ((int) $left->id <=> (int) $right->id);
            })
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();

        return $matches !== [] ? $matches : null;
    }

    /**
     * Existing snapshots created before the unit-part rule self-heal on their
     * next read, without forcing every STD list to rebuild on every request.
     *
     * @param  list<int>  $unitPartComponentIds
     */
    protected function unitPartSnapshotNeedsRebuild(int $workorderId, array $unitPartComponentIds): bool
    {
        $rows = WorkorderStdProcessItem::query()
            ->where('workorder_id', $workorderId)
            ->get(['component_id', 'std_type', 'base_qty']);

        if ($rows->contains(function (WorkorderStdProcessItem $item) use ($unitPartComponentIds): bool {
            return ! in_array((int) $item->component_id, $unitPartComponentIds, true)
                || (int) $item->base_qty !== 1;
        })) {
            return true;
        }

        return $rows
            ->groupBy('std_type')
            ->contains(static fn (Collection $stdRows): bool => $stdRows->count() > 1);
    }

    protected function normalizePartNumberForComparison(?string $partNumber): string
    {
        return preg_replace('/[^\pL\pN]+/u', '', mb_strtoupper(trim((string) $partNumber))) ?? '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function snapshotRowsForWorkorder(Workorder $workorder, string $std): array
    {
        StdProcess::assertValidStd($std);

        $hasRows = $this->hasRowsForWorkorder((int) $workorder->id);
        $unitPartComponentIds = $hasRows
            ? $this->overhaulUnitPartComponentIds(
                $workorder,
                app(ManualIplBranchRuleResolver::class)
            )
            : null;
        $unitPartSnapshotNeedsRebuild = $unitPartComponentIds !== null
            && $this->unitPartSnapshotNeedsRebuild((int) $workorder->id, $unitPartComponentIds);

        if (! $hasRows || $unitPartSnapshotNeedsRebuild) {
            $this->rebuild($workorder);
        }

        $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($workorder, $std);
        $rows = WorkorderStdProcessItem::query()
            ->where('workorder_id', $workorder->id)
            ->where('std_type', $std)
            ->with('component:id,kit_prl_choice_group')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (WorkorderStdProcessItem $item) use ($coverage): array {
                $row = $item->toSnapshotRow();
                $covered = $coverage[(int) $item->component_id] ?? null;
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
            ->all();

        return StdProcess::sortRowsForSnapshot($rows);
    }

    public function hasRowsForWorkorder(int $workorderId): bool
    {
        return WorkorderStdProcessItem::query()
            ->where('workorder_id', $workorderId)
            ->exists();
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
     * @return array<int, int>
     */
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
