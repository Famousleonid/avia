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
    public function isExplicitSelection(array $row): bool
    {
        return (string) ($row['assy_selection_explicit'] ?? '') === '1';
    }

    /** Only own assembly identities, not every ancestor in a BOM. */
    public function assemblyChoicesByComponent(Collection $groups, Collection $components): array
    {
        $members = app(ManualPartGroupCompositionResolver::class)->componentIdsByGroup($groups);
        $choices = [];
        foreach ($groups->where('type', ManualPartGroup::TYPE_ASSY) as $group) {
            foreach ($group->options as $option) {
                $optionMembers = $members[$group->id] ?? collect();
                if ($group->options->count() > 1) {
                    $specificGroup = clone $group;
                    $specificGroup->setRelation('options', collect([$option]));
                    $specificMembers = app(ManualPartGroupCompositionResolver::class)->componentIdsByGroup(
                        $groups->map(fn ($candidate) => $candidate->id === $group->id ? $specificGroup : $candidate)
                    );
                    $optionMembers = $specificMembers[$group->id] ?? collect();
                }
                foreach ($components as $component) {
                    if ((int) $component->id !== (int) $option->component_id
                        && $optionMembers->contains((int) $component->id)
                        && $this->hasOwnAssembly($component, (string) $option->part_number)) {
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
        $visited = [];
        $allowed = [];
        $visit = function (int $id) use (&$visit, &$visited, &$allowed, $options): void {
            if (isset($visited[$id])) {
                return;
            }
            $visited[$id] = true;
            $option = $options->get($id);
            if (! $option) {
                return;
            }
            $allowed[(int) $option->manual_part_group_id] = true;
            foreach ($option->coverages as $coverage) {
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
        if ($workorder && ! $workorder->done_at && $workorder->scope_type === Unit::SCOPE_PART_GROUP_OPTION && $components->isNotEmpty()) {
            $scope = ManualPartGroupOption::with('group')->find($workorder->scope_part_group_option_id);
            $groups = ManualPartGroup::where('manual_id', $scope?->group?->manual_id)
                ->with('options.coverages')->get();
            $members = app(ManualPartGroupCompositionResolver::class)->componentIdsByGroup($groups);
            foreach ($this->groupsForWorkorder($groups, $workorder)->where('type', ManualPartGroup::TYPE_ASSY) as $group) {
                $option = $group->options->first();
                if (! $option) {
                    continue;
                }
                foreach ($components as $component) {
                    if (($members[$group->id] ?? collect())->contains((int) $component->id)
                        && (int) $component->id !== (int) $option->component_id
                        && $this->hasOwnAssembly($component, (string) $option->part_number)) {
                        $scopedChoices[(int) $component->id][] = $option;
                    }
                }
            }
        }

        foreach ($rows as &$row) {
            if (! is_array($row) || ($row['manual_part_group_choice'] ?? '') !== 'component') {
                continue;
            }
            // An explicitly selected, server-validated identity is a saved snapshot.
            if ($this->isExplicitSelection($row)) {
                continue;
            }
            $component = $components->get((int) ($row['component_id'] ?? 0));
            $choices = $scopedChoices[(int) ($row['component_id'] ?? 0)] ?? [];
            // Correct only an unambiguous scoped identity. Reading never writes history.
            if (count($choices) === 1) {
                $option = $choices[0];
                $row['manual_part_group_id'] = (string) $option->manual_part_group_id;
                unset($row['manual_part_group_option_id']);
                $row['component_assembly_id'] = '';
                $row['assy_part_number'] = (string) $option->part_number;
                $row['assy_ipl_num'] = (string) $option->ipl_num;
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
