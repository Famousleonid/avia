<?php

namespace App\Services;

use App\Models\Component;
use App\Models\ManualPartGroup;
use App\Models\ManualPartGroupOption;
use App\Models\StdProcess;
use App\Models\Unit;
use App\Models\Workorder;
use Illuminate\Support\Collection;

class WorkorderPartScopeResolver
{
    /**
     * NULL means the complete manual package is in scope. Otherwise the result
     * maps component ids to the received quantity inside the scoped article.
     *
     * @return array<int, int>|null
     */
    public function componentQuantities(Workorder $workorder, ?string $formScope = null): ?array
    {
        if ($workorder->relationLoaded('unit')
            && (int) ($workorder->unit?->id ?? 0) !== (int) $workorder->unit_id) {
            $workorder->unsetRelation('unit');
        }
        if ($workorder->relationLoaded('instruction')
            && (int) ($workorder->instruction?->id ?? 0) !== (int) $workorder->instruction_id) {
            $workorder->unsetRelation('instruction');
        }
        $workorder->loadMissing(['unit', 'instruction:id,name']);

        return match ($workorder->scope_type) {
            Unit::SCOPE_COMPONENT => $workorder->scope_component_id
                ? [(int) $workorder->scope_component_id => 1]
                : [],
            Unit::SCOPE_PART_GROUP_OPTION => $workorder->scope_part_group_option_id
                ? $this->optionComponentQuantities((int) $workorder->scope_part_group_option_id, $formScope)
                : [],
            Unit::SCOPE_FULL_UNIT => null,
            default => $this->legacyComponentQuantities($workorder),
        };
    }

    public function filterComponents(Collection $components, Workorder $workorder, ?string $formScope = null): Collection
    {
        $quantities = $this->componentQuantities($workorder, $formScope);
        if ($quantities === null) {
            return $components->values();
        }

        return $components
            ->filter(fn (Component $component): bool => isset($quantities[(int) $component->id]))
            ->values();
    }

    public function allowsComponent(Workorder $workorder, int $componentId, ?string $formScope = null): bool
    {
        $quantities = $this->componentQuantities($workorder, $formScope);

        return $quantities === null || isset($quantities[$componentId]);
    }

    public function displayLabelForUnit(Unit $unit): string
    {
        $unit->loadMissing(['defaultScopeComponent', 'defaultScopePartGroupOption.group']);

        if ($unit->default_scope_type === Unit::SCOPE_COMPONENT && $unit->defaultScopeComponent) {
            return sprintf(
                'Part: %s · IPL %s',
                trim((string) $unit->defaultScopeComponent->part_number),
                trim((string) $unit->defaultScopeComponent->ipl_num)
            );
        }

        if ($unit->default_scope_type === Unit::SCOPE_PART_GROUP_OPTION && $unit->defaultScopePartGroupOption) {
            $type = strtoupper((string) ($unit->defaultScopePartGroupOption->group?->type ?? 'ASSY'));

            return sprintf(
                '%s: %s · IPL %s',
                $type,
                trim((string) $unit->defaultScopePartGroupOption->part_number),
                trim((string) $unit->defaultScopePartGroupOption->ipl_num)
            );
        }

        return 'Complete Unit';
    }

    public function displayLabelForWorkorder(Workorder $workorder): string
    {
        $workorder->loadMissing(['scopeComponent', 'scopePartGroupOption.group']);

        if ($workorder->scope_type === Unit::SCOPE_COMPONENT && $workorder->scopeComponent) {
            return sprintf(
                'Part: %s · IPL %s',
                trim((string) $workorder->scopeComponent->part_number),
                trim((string) $workorder->scopeComponent->ipl_num)
            );
        }

        if ($workorder->scope_type === Unit::SCOPE_PART_GROUP_OPTION && $workorder->scopePartGroupOption) {
            $type = strtoupper((string) ($workorder->scopePartGroupOption->group?->type ?? 'ASSY'));

            return sprintf(
                '%s: %s · IPL %s',
                $type,
                trim((string) $workorder->scopePartGroupOption->part_number),
                trim((string) $workorder->scopePartGroupOption->ipl_num)
            );
        }

        if ($workorder->scope_type === null) {
            $quantities = $this->componentQuantities($workorder);
            if ($quantities !== null && $quantities !== []) {
                $component = Component::withTrashed()->find(array_key_first($quantities));
                if ($component) {
                    return sprintf(
                        'Part: %s · IPL %s (legacy)',
                        trim((string) $component->part_number),
                        trim((string) $component->ipl_num)
                    );
                }
            }
        }

        return $workorder->scope_type === null ? 'Complete Unit (legacy)' : 'Complete Unit';
    }

    /** @return array<int, int> */
    private function optionComponentQuantities(int $optionId, ?string $formScope): array
    {
        $result = [];
        $this->collectOptionComponents($optionId, $formScope, 1, $result, []);

        return $result;
    }

    /**
     * @param  array<int, int>  $result
     * @param  array<int, bool>  $visited
     */
    private function collectOptionComponents(
        int $optionId,
        ?string $formScope,
        int $multiplier,
        array &$result,
        array $visited
    ): void {
        if (isset($visited[$optionId])) {
            return;
        }
        $visited[$optionId] = true;

        $option = ManualPartGroupOption::query()
            ->with(['group.options', 'coverages'])
            ->find($optionId);
        if (! $option) {
            return;
        }

        if ($option->group?->type === ManualPartGroup::TYPE_OVERSIZE) {
            foreach ($option->group->options as $bushingOption) {
                $componentId = (int) ($bushingOption->component_id ?? 0);
                if ($componentId > 0) {
                    $result[$componentId] = max($result[$componentId] ?? 0, max(1, $multiplier));
                }
            }

            return;
        }

        if ($option->component_id) {
            $componentId = (int) $option->component_id;
            $result[$componentId] = max($result[$componentId] ?? 0, max(1, $multiplier));
        }

        foreach ($option->coverages as $coverage) {
            if ($formScope !== null && ! $coverage->appliesTo($formScope)) {
                continue;
            }

            $qty = max(1, (int) ($coverage->qty ?? 1)) * max(1, $multiplier);
            if ($coverage->component_id) {
                $componentId = (int) $coverage->component_id;
                $result[$componentId] = max($result[$componentId] ?? 0, $qty);
            }
            if ($coverage->covered_manual_part_group_option_id) {
                $this->collectOptionComponents(
                    (int) $coverage->covered_manual_part_group_option_id,
                    $formScope,
                    $qty,
                    $result,
                    $visited
                );
            }
        }
    }

    /**
     * Preserve the old Overhaul P/N inference only for workorders predating
     * explicit scope snapshots. New workorders always store a scope_type.
     *
     * @return array<int, int>|null
     */
    private function legacyComponentQuantities(Workorder $workorder): ?array
    {
        if (strcasecmp(trim((string) ($workorder->instruction?->name ?? '')), 'Overhaul') !== 0) {
            return null;
        }

        $unit = $workorder->unit;
        $manualId = (int) ($unit?->manual_id ?? 0);
        $unitPartNumber = $this->normalizePartNumber($unit?->part_number);
        if (! $unit || $manualId <= 0 || $unitPartNumber === '') {
            return null;
        }

        $branchResolver = app(ManualIplBranchRuleResolver::class);
        $matches = Component::query()
            ->where('manual_id', $manualId)
            ->whereNotNull('part_number')
            ->get(['id', 'manual_id', 'ipl_num', 'part_number', 'eff_code'])
            ->filter(function (Component $component) use ($unit, $manualId, $unitPartNumber, $branchResolver): bool {
                return $this->normalizePartNumber($component->part_number) === $unitPartNumber
                    && $branchResolver->allowsComponentForUnit($unit, (string) ($component->ipl_num ?? ''), $manualId)
                    && StdProcess::stdRowEffMatchesUnit($component->eff_code, (string) ($unit->eff_code ?? ''));
            })
            ->sort(fn (Component $left, Component $right): int =>
                StdProcess::compareIplValues($left->ipl_num, $right->ipl_num)
                    ?: ((int) $left->id <=> (int) $right->id)
            )
            ->pluck('id')
            ->mapWithKeys(fn ($id): array => [(int) $id => 1])
            ->all();

        return $matches !== [] ? $matches : null;
    }

    private function normalizePartNumber(?string $partNumber): string
    {
        return preg_replace('/[^\pL\pN]+/u', '', mb_strtoupper(trim((string) $partNumber))) ?? '';
    }
}
