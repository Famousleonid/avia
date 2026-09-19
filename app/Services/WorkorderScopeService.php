<?php

namespace App\Services;

use App\Models\Component;
use App\Models\ManualPartGroup;
use App\Models\ManualPartGroupOption;
use App\Models\Unit;
use App\Models\Workorder;
use Illuminate\Validation\ValidationException;

class WorkorderScopeService
{
    public const MODE_PART_ASSEMBLY = 'part_assembly';

    /**
     * @return array{scope_type:?string,scope_component_id:?int,scope_part_group_option_id:?int}
     */
    public function values(Workorder $workorder): array
    {
        return [
            'scope_type' => $workorder->scope_type,
            'scope_component_id' => $workorder->scope_component_id
                ? (int) $workorder->scope_component_id
                : null,
            'scope_part_group_option_id' => $workorder->scope_part_group_option_id
                ? (int) $workorder->scope_part_group_option_id
                : null,
        ];
    }

    /**
     * The UI has two business choices. Component and ASSY remain separate only
     * in storage because their downstream coverage rules are different.
     *
     * @return array{mode:string,target:string}
     */
    public function businessSelectionForWorkorder(Workorder $workorder): array
    {
        if ($workorder->scope_type === Unit::SCOPE_COMPONENT && $workorder->scope_component_id) {
            return $this->partSelection('component', (int) $workorder->scope_component_id);
        }

        if ($workorder->scope_type === Unit::SCOPE_PART_GROUP_OPTION && $workorder->scope_part_group_option_id) {
            return $this->partSelection('part_group_option', (int) $workorder->scope_part_group_option_id);
        }

        if ($workorder->scope_type === null) {
            $quantities = app(WorkorderPartScopeResolver::class)->componentQuantities($workorder);
            if (is_array($quantities) && $quantities !== []) {
                return $this->partSelection('component', (int) array_key_first($quantities));
            }
        }

        return ['mode' => Unit::SCOPE_FULL_UNIT, 'target' => ''];
    }

    /**
     * @return array{scope_type:?string,scope_component_id:?int,scope_part_group_option_id:?int}
     */
    public function normalizeSelection(array $payload, Unit $unit, Workorder $workorder): array
    {
        $mode = trim((string) ($payload['scope_type'] ?? ''));
        $manualId = (int) ($unit->manual_id ?? 0);

        // Keep accepting the original technical contract for compatibility
        // with requests/tests created before the two-choice Work Scope UI.
        if ($mode === Unit::SCOPE_COMPONENT) {
            return $this->componentSelection(
                $manualId,
                (int) ($payload['scope_target_id'] ?? $payload['scope_component_id'] ?? 0)
            );
        }

        if ($mode === Unit::SCOPE_PART_GROUP_OPTION) {
            return $this->assySelection(
                $manualId,
                (int) ($payload['scope_target_id'] ?? $payload['scope_part_group_option_id'] ?? 0)
            );
        }

        if ($mode === Unit::SCOPE_FULL_UNIT) {
            $selection = [
                'scope_type' => Unit::SCOPE_FULL_UNIT,
                'scope_component_id' => null,
                'scope_part_group_option_id' => null,
            ];

            return $this->preserveLegacyWhenUnchanged($unit, $workorder, $mode, '', $selection);
        }

        if ($mode !== self::MODE_PART_ASSEMBLY) {
            throw ValidationException::withMessages([
                'scope_type' => [__('Select Complete Unit or Part / Assembly.')],
            ]);
        }

        $target = trim((string) ($payload['scope_target_id'] ?? ''));
        if (! preg_match('/^(component|part_group_option):(\d+)$/', $target, $matches)) {
            throw ValidationException::withMessages([
                'scope_target_id' => [__('Select the Part or Assembly received for this Workorder.')],
            ]);
        }

        $selection = $matches[1] === 'component'
            ? $this->componentSelection($manualId, (int) $matches[2])
            : $this->assySelection($manualId, (int) $matches[2]);

        return $this->preserveLegacyWhenUnchanged($unit, $workorder, $mode, $target, $selection);
    }

    /**
     * @return array{
     *     unit:array<string,mixed>,
     *     scope_targets:list<array{value:string,label:string,kind:string}>,
     *     scope_components:list<array{id:int,label:string}>,
     *     scope_group_options:list<array{id:int,label:string}>
     * }
     */
    public function optionsForUnit(Unit $unit): array
    {
        $unit->loadMissing(['manual', 'defaultScopeComponent', 'defaultScopePartGroupOption.group']);
        $manualId = (int) ($unit->manual_id ?? 0);

        $components = $manualId > 0
            ? Component::query()
                ->where('manual_id', $manualId)
                ->orderByRaw('CASE WHEN ipl_num IS NULL OR ipl_num = ? THEN 1 ELSE 0 END', [''])
                ->orderBy('ipl_num')
                ->orderBy('part_number')
                ->get(['id', 'ipl_num', 'part_number', 'name'])
            : collect();

        $assyOptions = $manualId > 0
            ? ManualPartGroupOption::query()
                ->whereHas('group', fn ($query) => $query
                    ->where('manual_id', $manualId)
                    ->where('type', ManualPartGroup::TYPE_ASSY))
                ->with(['group:id,type,name', 'component:id,name'])
                ->orderBy('ipl_num')
                ->orderBy('part_number')
                ->get(['id', 'manual_part_group_id', 'component_id', 'part_number', 'ipl_num', 'label'])
            : collect();

        // An ASSY root is selectable through its assembly scope only. Do not
        // match by P/N: another IPL position can share the same part number.
        $assyComponentIds = $assyOptions->pluck('component_id')->filter()->all();
        $componentRows = $components
            ->reject(fn (Component $component): bool => in_array((int) $component->id, $assyComponentIds, true))
            ->map(fn (Component $component): array => [
                'id' => (int) $component->id,
                'label' => $this->componentLabel($component),
            ])
            ->values();
        $assyRows = $assyOptions
            ->map(fn (ManualPartGroupOption $option): array => [
                'id' => (int) $option->id,
                'label' => $this->assyLabel($option),
            ])
            ->values();

        $targets = $componentRows
            ->map(fn (array $row): array => [
                'value' => 'component:'.$row['id'],
                'label' => $row['label'].' · Part',
                'kind' => 'part',
            ])
            ->concat($assyRows->map(fn (array $row): array => [
                'value' => 'part_group_option:'.$row['id'],
                'label' => $row['label'].' · ASSY',
                'kind' => 'assembly',
            ]))
            ->sortBy(fn (array $row): string => $row['label'], SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        $defaultSelection = $this->businessSelectionForUnit($unit);

        return [
            'unit' => [
                'id' => (int) $unit->id,
                'manual_id' => $manualId ?: null,
                'default_scope_mode' => $defaultSelection['mode'],
                'default_scope_target' => $defaultSelection['target'],
                // Keep the original fields for older callers.
                'default_scope_type' => $unit->default_scope_type ?: Unit::SCOPE_FULL_UNIT,
                'default_scope_component_id' => $unit->default_scope_component_id
                    ? (int) $unit->default_scope_component_id
                    : null,
                'default_scope_part_group_option_id' => $unit->default_scope_part_group_option_id
                    ? (int) $unit->default_scope_part_group_option_id
                    : null,
                'scope_display' => app(WorkorderPartScopeResolver::class)->displayLabelForUnit($unit),
            ],
            'scope_targets' => $targets,
            'scope_components' => $componentRows->all(),
            'scope_group_options' => $assyRows->all(),
        ];
    }

    /** @return array{mode:string,target:string} */
    private function businessSelectionForUnit(Unit $unit): array
    {
        if ($unit->default_scope_type === Unit::SCOPE_COMPONENT && $unit->default_scope_component_id) {
            return $this->partSelection('component', (int) $unit->default_scope_component_id);
        }

        if ($unit->default_scope_type === Unit::SCOPE_PART_GROUP_OPTION
            && $unit->default_scope_part_group_option_id
            && $unit->defaultScopePartGroupOption?->group?->type === ManualPartGroup::TYPE_ASSY) {
            return $this->partSelection('part_group_option', (int) $unit->default_scope_part_group_option_id);
        }

        return ['mode' => Unit::SCOPE_FULL_UNIT, 'target' => ''];
    }

    /**
     * @param array{scope_type:?string,scope_component_id:?int,scope_part_group_option_id:?int} $selection
     * @return array{scope_type:?string,scope_component_id:?int,scope_part_group_option_id:?int}
     */
    private function preserveLegacyWhenUnchanged(
        Unit $unit,
        Workorder $workorder,
        string $mode,
        string $target,
        array $selection
    ): array {
        if ($workorder->scope_type !== null || (int) $workorder->unit_id !== (int) $unit->id) {
            return $selection;
        }

        $current = $this->businessSelectionForWorkorder($workorder);

        return $current['mode'] === $mode && $current['target'] === $target
            ? $this->values($workorder)
            : $selection;
    }

    /** @return array{scope_type:string,scope_component_id:int,scope_part_group_option_id:null} */
    private function componentSelection(int $manualId, int $componentId): array
    {
        $valid = $manualId > 0 && $componentId > 0
            && Component::query()
                ->whereKey($componentId)
                ->where('manual_id', $manualId)
                ->exists();
        if (! $valid) {
            throw ValidationException::withMessages([
                'scope_target_id' => [__('Select a Part from this Unit CMM for the Work Scope.')],
            ]);
        }

        return [
            'scope_type' => Unit::SCOPE_COMPONENT,
            'scope_component_id' => $componentId,
            'scope_part_group_option_id' => null,
        ];
    }

    /** @return array{scope_type:string,scope_component_id:null,scope_part_group_option_id:int} */
    private function assySelection(int $manualId, int $optionId): array
    {
        $valid = $manualId > 0 && $optionId > 0
            && ManualPartGroupOption::query()
                ->whereKey($optionId)
                ->whereHas('group', fn ($query) => $query
                    ->where('manual_id', $manualId)
                    ->where('type', ManualPartGroup::TYPE_ASSY))
                ->exists();
        if (! $valid) {
            throw ValidationException::withMessages([
                'scope_target_id' => [__('Select an Assembly from this Unit CMM for the Work Scope.')],
            ]);
        }

        return [
            'scope_type' => Unit::SCOPE_PART_GROUP_OPTION,
            'scope_component_id' => null,
            'scope_part_group_option_id' => $optionId,
        ];
    }

    /** @return array{mode:string,target:string} */
    private function partSelection(string $type, int $id): array
    {
        return [
            'mode' => self::MODE_PART_ASSEMBLY,
            'target' => $type.':'.$id,
        ];
    }

    private function componentLabel(Component $component): string
    {
        return trim(implode(' · ', array_filter([
            $component->ipl_num ?: null,
            (string) $component->part_number,
            (string) $component->name,
        ])));
    }

    private function assyLabel(ManualPartGroupOption $option): string
    {
        return trim(implode(' · ', array_filter([
            $option->ipl_num ?: null,
            (string) $option->part_number,
            (string) ($option->component?->name ?: $option->label ?: $option->group?->name),
        ])));
    }
}
