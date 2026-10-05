<?php

namespace App\Services;

use App\Models\Component;
use App\Models\ManualPartGroup;
use App\Models\ManualPartGroupOption;
use App\Models\Unit;
use App\Models\Workorder;
use Illuminate\Support\Collection;

class LogCardAssemblyIdentity
{
    /** A BOM is not a list of alternatives. Only suffix variants share a radio row. */
    public function logCardFamilyKey(array $choice): string
    {
        $ipl = trim((string) ($choice['ipl_num'] ?? ''));
        if (preg_match('/^([A-Za-z0-9]+-\d+)[A-Za-z]*$/', $ipl, $matches)) {
            return $matches[1];
        }

        return 'component_'.(int) $choice['component_id'];
    }

    public function isExplicitSelection(array $row): bool
    {
        return (string) ($row['assy_selection_explicit'] ?? '') === '1';
    }

    /** An explicitly empty selection must stay empty on save, reopen and print. */
    public function isExplicitNoAssembly(array $row): bool
    {
        return $this->isExplicitSelection($row)
            && ($row['manual_part_group_choice'] ?? '') === 'component'
            && (int) ($row['manual_part_group_id'] ?? 0) === 0
            && (int) ($row['assy_option_id'] ?? 0) === 0
            && (int) ($row['manual_part_group_option_id'] ?? 0) === 0;
    }

    public function withoutAssembly(array $row): array
    {
        $row['assy_selection_explicit'] = '1';
        $row['manual_part_group_choice'] = 'component';
        foreach (['manual_part_group_id', 'manual_part_group_option_id', 'assy_option_id',
            'component_assembly_id', 'assy_part_number', 'assy_ipl_num', 'assy_serial_number'] as $key) {
            $row[$key] = '';
        }
        return $row;
    }

    /** New groups are authoritative. Traverse variant families, but stop at a nested ASSY. */
    public function assemblyChoicesByComponent(Collection $groups, Collection $components, ?Workorder $workorder = null): array
    {
        $optionTypes = collect();
        foreach ($groups as $group) {
            foreach ($group->options as $option) {
                $optionTypes->put((int) $option->id, $group->type);
            }
        }
        $configured = $workorder ? app(WorkorderAssyConfiguration::class)->selectedCoverageIds($workorder) : null;
        $groups = $groups->map(function ($group) use ($optionTypes, $configured) {
            $copy = clone $group;
            $copy->setRelation('options', $group->options->map(function ($option) use ($optionTypes, $configured) {
                $copy = clone $option;
                $coverages = $configured === null ? $option->coverages
                    : app(WorkorderAssyConfiguration::class)->members($option, $configured);
                $copy->setRelation('coverages', $coverages->reject(fn ($coverage) =>
                    $optionTypes->get((int) $coverage->covered_manual_part_group_option_id) === ManualPartGroup::TYPE_ASSY
                )->map(function ($coverage) {
                    // Assembly identity follows the actual catalog member, not
                    // every letter variant used by BOM/PRL family expansion.
                    $copy = clone $coverage;
                    $copy->expand_ipl_family = false;
                    return $copy;
                })->values());
                return $copy;
            }));
            return $copy;
        });
        $members = app(ManualPartGroupCompositionResolver::class)->componentIdsByGroup($groups, $workorder);
        $choices = [];
        foreach ($groups->where('type', ManualPartGroup::TYPE_ASSY) as $group) {
            foreach ($group->options as $option) {
                $optionMembers = $members[$group->id] ?? collect();
                if ($group->options->count() > 1) {
                    $specificGroup = clone $group;
                    $specificGroup->setRelation('options', collect([$option]));
                    $specificMembers = app(ManualPartGroupCompositionResolver::class)->componentIdsByGroup(
                        $groups->map(fn ($candidate) => $candidate->id === $group->id ? $specificGroup : $candidate),
                        $workorder
                    );
                    $optionMembers = $specificMembers[$group->id] ?? collect();
                }
                foreach ($components as $component) {
                    if ((int) $component->id !== (int) $option->component_id
                        && (int) $component->manual_id === (int) $group->manual_id
                        && $optionMembers->contains((int) $component->id)) {
                        $choices[(int) $component->id][] = [
                            'group_id' => (int) $group->id,
                            'option_id' => (int) $option->id,
                            'part_number' => (string) $option->part_number,
                            'ipl_num' => (string) $option->ipl_num,
                        ];
                    }
                }
            }
        }
        return $choices;
    }

    /** Resolve a technician's choice; group-only legacy requests are valid only if unambiguous. */
    public function selectedAssemblyChoice(array $row, array $choices): ?array
    {
        $matches = collect($choices)->where('group_id', (int) ($row['manual_part_group_id'] ?? 0));
        if ((int) ($row['assy_option_id'] ?? 0) > 0) {
            $matches = $matches->where('option_id', (int) $row['assy_option_id']);
        }
        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function needsAssemblyChoice(array $row): bool
    {
        return ($row['manual_part_group_choice'] ?? '') !== 'assy'
            && (int) ($row['manual_part_group_id'] ?? 0) === 0
            && (int) ($row['manual_part_group_option_id'] ?? 0) === 0
            && (int) ($row['assy_option_id'] ?? 0) === 0;
    }

    /** A single catalog parent is automatic; multiple parents require a choice. */
    public function withAutomaticAssembly(array $row, array $choices): array
    {
        if ($this->isExplicitNoAssembly($row) || ! $this->needsAssemblyChoice($row) || count($choices) !== 1) {
            return $row;
        }
        $choice = $choices[0];
        return array_merge($row, [
            'manual_part_group_choice' => 'component',
            'manual_part_group_id' => (string) $choice['group_id'],
            'manual_part_group_option_id' => '',
            'assy_option_id' => (string) $choice['option_id'],
            'assy_selection_explicit' => '1',
            'component_assembly_id' => '',
            'assy_part_number' => $choice['part_number'],
            'assy_ipl_num' => $choice['ipl_num'],
        ]);
    }

    /** Incoming Log Card follows the received WO scope, not a later R&M conversion. */
    public function groupsForWorkorder(Collection $groups, Workorder $workorder): Collection
    {
        if ($workorder->scope_type !== Unit::SCOPE_PART_GROUP_OPTION) {
            return $groups;
        }
        $rootId = (int) $workorder->scope_part_group_option_id;
        $root = ManualPartGroupOption::with('group')->find($rootId);
        $manualId = (int) ($root?->group?->manual_id ?? $workorder->unit?->manual_id);
        $options = $groups->flatMap(fn ($group) => $group->options)->keyBy('id');
        $configuredMembers = app(WorkorderAssyConfiguration::class)->selectedCoverageIds($workorder);
        $visited = [];
        $allowed = [];
        $visit = function (int $id) use (&$visit, &$visited, &$allowed, $options, $configuredMembers): void {
            if (isset($visited[$id])) {
                return;
            }
            $visited[$id] = true;
            $option = $options->get($id);
            if (! $option) {
                return;
            }
            $allowed[(int) $option->manual_part_group_id] = true;
            foreach (app(WorkorderAssyConfiguration::class)->members($option, $configuredMembers) as $coverage) {
                if ($coverage->covered_manual_part_group_option_id) {
                    $visit((int) $coverage->covered_manual_part_group_option_id);
                }
            }
        };
        $visit($rootId);

        return $groups->filter(fn ($group) => (int) $group->manual_id !== $manualId || isset($allowed[(int) $group->id]))
            ->sortBy(fn ($group) => (int) $group->id === (int) $root?->manual_part_group_id ? 0 : 1)
            ->values();
    }

    /** Legacy identity lookup for historical rows only; never gates new group choices. */
    public function hasOwnAssembly(Component $component, string $partNumber): bool
    {
        $partNumber = trim($partNumber);
        if ($partNumber === '') {
            return false;
        }

        return strcasecmp(trim((string) $component->assy_part_number), $partNumber) === 0
            || $component->assemblies->contains(fn ($assembly): bool =>
                strcasecmp(trim((string) $assembly->assy_part_number), $partNumber) === 0);
    }

    /** Project an unambiguous received scope for active cards; never persist on read. */
    public function cleanRows(array $rows, ?Workorder $workorder = null): array
    {
        $components = Component::with('assemblies')->whereIn('id', collect($rows)
            ->filter(fn ($row): bool => is_array($row) && ($row['manual_part_group_choice'] ?? '') === 'component')
            ->pluck('component_id')->filter()->unique())->get()->keyBy('id');

        $scopedChoices = [];
        $groupChoices = [];
        if ($components->isNotEmpty()) {
            $groups = ManualPartGroup::whereIn('manual_id', $components->pluck('manual_id')->unique())
                ->with('options.coverages')->get();
            $groupChoices = $this->assemblyChoicesByComponent($groups, $components, $workorder);
        }
        if ($workorder && ! $workorder->done_at && $workorder->scope_type === Unit::SCOPE_PART_GROUP_OPTION && $components->isNotEmpty()) {
            $scope = ManualPartGroupOption::with('group')->find($workorder->scope_part_group_option_id);
            $groups = ManualPartGroup::where('manual_id', $scope?->group?->manual_id)
                ->with('options.coverages')->get();
            $allowedGroups = $this->groupsForWorkorder($groups, $workorder)->pluck('id');
            foreach ($groupChoices as $componentId => $choices) {
                $scopedChoices[$componentId] = array_values(array_filter($choices,
                    fn ($choice) => $allowedGroups->contains($choice['group_id'])));
            }
        }

        foreach ($rows as &$row) {
            if (! is_array($row) || ($row['manual_part_group_choice'] ?? '') !== 'component') {
                continue;
            }
            if (! $workorder?->done_at) {
                $row = $this->withAutomaticAssembly($row, $groupChoices[(int) ($row['component_id'] ?? 0)] ?? []);
            }
            // An explicitly selected, server-validated identity is a saved snapshot.
            if ($this->isExplicitSelection($row) || $workorder?->done_at) {
                continue;
            }
            $component = $components->get((int) ($row['component_id'] ?? 0));
            $choices = $scopedChoices[(int) ($row['component_id'] ?? 0)] ?? [];
            // Correct only an unambiguous scoped identity. Reading never writes history.
            if (count($choices) === 1) {
                $option = $choices[0];
                $row['manual_part_group_id'] = (string) $option['group_id'];
                unset($row['manual_part_group_option_id']);
                $row['component_assembly_id'] = '';
                $row['assy_part_number'] = (string) $option['part_number'];
                $row['assy_ipl_num'] = (string) $option['ipl_num'];
                continue;
            }
            // A valid new-group identity does not need duplicate legacy links.
            $selected = $this->selectedAssemblyChoice($row, $groupChoices[(int) ($row['component_id'] ?? 0)] ?? []);
            if ($selected && strcasecmp(trim((string) ($row['assy_part_number'] ?? '')), $selected['part_number']) === 0) {
                continue;
            }
            if (! $component || $this->hasOwnAssembly($component, (string) ($row['assy_part_number'] ?? ''))) {
                continue;
            }
            // A real component_assembly_id is stronger evidence than an old group label.
            $assembly = $component->assemblies->firstWhere('id', (int) ($row['component_assembly_id'] ?? 0));
            $row['assy_part_number'] = (string) ($assembly?->assy_part_number ?? $component->assy_part_number ?? '');
            $row['assy_ipl_num'] = (string) ($assembly?->assy_ipl_num ?? $component->assy_ipl_num ?? '');
            $row['component_assembly_id'] = $assembly ? (string) $assembly->id : '';
        }
        unset($row);

        return $rows;
    }
}
