<?php

namespace App\Services;

use App\Models\Code;
use App\Models\Component;
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
        $workorder->loadMissing('unit.manuals');
        $branchResolver = app(ManualIplBranchRuleResolver::class);

        DB::transaction(function () use ($workorder, $branchResolver): void {
            WorkorderStdProcessItem::query()
                ->where('workorder_id', $workorder->id)
                ->delete();

            $manual = $workorder->unit->manuals ?? null;
            if (! $manual) {
                return;
            }

            $excludedQtyByComponent = $this->excludedQtyByComponent($workorder);
            $excludedQtyByBaseIpl = $this->excludedQtyByBaseIpl($workorder);
            $now = now();
            $insertRows = [];

            StdProcess::syncFromComponentFlagsForManualWhenCountsDiffer($manual);

            foreach (StdProcess::validStdValues() as $std) {
                $manualRows = $this->manualStdRowsForManualStd((int) $manual->id, $std);
                $effCodedBaseIpls = $this->effCodedBaseIpls($manualRows);
                $flagColumn = $this->componentFlagColumnForStd($std);
                $sortOrder = 1;

                $eligibleRows = [];

                foreach ($manualRows as $manualRow) {
                    $component = $manualRow->component;
                    if (! $component || ! (bool) $component->{$flagColumn}) {
                        continue;
                    }

                    if (! $branchResolver->allowsComponentForUnit($workorder->unit, (string) ($component->ipl_num ?? ''), (int) $manual->id)) {
                        continue;
                    }

                    $rowEff = $manualRow->eff_code ?? $component->eff_code;
                    if ($this->shouldSkipUniversalVariantBecauseEffVariantsExist($component, $rowEff, $effCodedBaseIpls)) {
                        continue;
                    }

                    if (! StdProcess::stdRowEffMatchesUnit($rowEff, (string) ($workorder->unit->eff_code ?? ''))) {
                        continue;
                    }

                    $eligibleRows[] = [
                        'manual_row' => $manualRow,
                        'component' => $component,
                        'row_eff' => $rowEff,
                    ];
                }

                $collapsedVariantBaseKeys = $this->collapsedSuffixVariantBaseKeys($eligibleRows, (string) ($manual->number ?? ''));

                foreach ($eligibleRows as $eligibleRow) {
                    /** @var StdProcess $manualRow */
                    $manualRow = $eligibleRow['manual_row'];
                    /** @var Component $component */
                    $component = $eligibleRow['component'];
                    $rowEff = $eligibleRow['row_eff'];

                    $baseQty = $this->baseQty($component, $manualRow);
                    $excludedSourceQty = (int) ($excludedQtyByComponent[$component->id] ?? 0);

                    foreach ($this->baseIplKeys((string) ($component->ipl_num ?? '')) as $baseKey) {
                        if (isset($collapsedVariantBaseKeys[$baseKey])) {
                            $excludedSourceQty = max($excludedSourceQty, (int) ($excludedQtyByBaseIpl[$baseKey] ?? 0));
                        }
                    }

                    $excludedQty = min($baseQty, $excludedSourceQty);
                    $remainingQty = $baseQty - $excludedQty;

                    if ($remainingQty <= 0) {
                        continue;
                    }

                    $insertRows[] = [
                        'workorder_id' => $workorder->id,
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
                        'sort_order' => $sortOrder++,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($insertRows, 500) as $chunk) {
                WorkorderStdProcessItem::query()->insert($chunk);
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function snapshotRowsForWorkorder(Workorder $workorder, string $std): array
    {
        StdProcess::assertValidStd($std);

        if (! $this->hasRowsForWorkorder((int) $workorder->id)) {
            $this->rebuild($workorder);
        }

        $rows = WorkorderStdProcessItem::query()
            ->where('workorder_id', $workorder->id)
            ->where('std_type', $std)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (WorkorderStdProcessItem $item): array => $item->toSnapshotRow())
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
                ->where('units.manual_id', $manualId))
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
            ->with('component:id,ipl_num')
            ->get(['component_id', 'qty'])
            ->each(function (Tdr $tdr) use (&$excluded): void {
                if (! $tdr->component) {
                    return;
                }

                $qty = max(1, (int) ($tdr->qty ?? 1));

                foreach ($this->baseIplKeys((string) ($tdr->component->ipl_num ?? '')) as $baseKey) {
                    $excluded[$baseKey] = ($excluded[$baseKey] ?? 0) + $qty;
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
     * @param Collection<int, StdProcess> $manualRows
     * @return array<string, bool>
     */
    protected function effCodedBaseIpls(Collection $manualRows): array
    {
        $keys = [];

        foreach ($manualRows as $manualRow) {
            $component = $manualRow->component;
            if (! $component) {
                continue;
            }

            $rowEff = $manualRow->eff_code ?? $component->eff_code;
            if (StdProcess::effCodeTokens($rowEff) === []) {
                continue;
            }

            foreach ($this->baseIplKeys((string) ($component->ipl_num ?? '')) as $baseKey) {
                $keys[$baseKey] = true;
            }
        }

        return $keys;
    }

    /**
     * @param array<string, bool> $effCodedBaseIpls
     */
    protected function shouldSkipUniversalVariantBecauseEffVariantsExist(Component $component, ?string $rowEff, array $effCodedBaseIpls): bool
    {
        if (StdProcess::effCodeTokens($rowEff) !== []) {
            return false;
        }

        foreach ($this->baseIplKeys((string) ($component->ipl_num ?? '')) as $baseKey) {
            if (isset($effCodedBaseIpls[$baseKey])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array{manual_row: StdProcess, component: Component, row_eff: mixed}> $eligibleRows
     * @return array<string, bool>
     */
    protected function collapsedSuffixVariantBaseKeys(array $eligibleRows, string $manualNumber): array
    {
        $groups = [];

        foreach ($eligibleRows as $eligibleRow) {
            /** @var Component $component */
            $component = $eligibleRow['component'];
            $ipl = trim((string) ($component->ipl_num ?? ''));
            if (! preg_match('/^(\d+[A-Za-z]*-\d+)([A-Za-z]+)$/', $ipl, $matches)) {
                continue;
            }

            $baseIpl = strtoupper((string) ($matches[1] ?? ''));
            $manual = trim((string) ($component->manual?->number ?? $manualNumber));
            $name = mb_strtoupper(trim((string) ($component->name ?? '')));
            $process = trim((string) ($eligibleRow['manual_row']->process ?? ''));
            $groupKey = implode('|', [$manual, $baseIpl, $name, $process]);

            $groups[$groupKey][$baseIpl] = true;
            $groups[$groupKey]['count'] = ($groups[$groupKey]['count'] ?? 0) + 1;
        }

        $baseKeys = [];

        foreach ($groups as $group) {
            if (($group['count'] ?? 0) < 2) {
                continue;
            }

            foreach ($group as $key => $value) {
                if ($key !== 'count') {
                    $baseKeys[(string) $key] = true;
                }
            }
        }

        return $baseKeys;
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
