<?php

namespace App\Services;

use App\Models\ManualPartGroup;
use Illuminate\Support\Collection;

class LogCardDraftChoiceGroups
{
    /** Group selectable parts independently of their parent ASSY; never combine BOM members. */
    public function merge(Collection $components, Collection $assyGroups, Collection $catalogGroups): Collection
    {
        $parts = $components->filter(fn ($part) => (int) ($part->units_assy ?? 1) === 1)->keyBy('id');
        $parent = array_combine($parts->keys()->all(), $parts->keys()->all());
        $find = function ($id) use (&$find, &$parent) {
            return $parent[$id] === $id ? $id : ($parent[$id] = $find($parent[$id]));
        };
        $join = function ($ids) use (&$parent, $find, $parts) {
            $ids = collect($ids)->filter(fn ($id) => $parts->has($id))->values();
            foreach ($ids as $id) $parent[$find($id)] = $find($ids->first());
        };
        $identity = app(LogCardAssemblyIdentity::class);
        foreach ($parts->groupBy(fn ($p) => $identity->logCardFamilyKey(['ipl_num' => $p->ipl_num, 'component_id' => $p->id])) as $family) {
            $join($family->pluck('id'));
        }
        foreach ($catalogGroups->where('type', ManualPartGroup::TYPE_ALTERNATIVE) as $group) {
            $join($group->options->pluck('component_id'));
        }
        $existing = collect();
        foreach ($assyGroups as $group) {
            foreach ($group['choices'] as $choice) {
                $existing->put($choice['component_id'], array_merge($choice, ['manual_part_group_id' => $group['group']->id]));
            }
        }

        return $parts->groupBy(fn ($p) => $find($p->id))->map(function ($family) use ($existing) {
            if ($family->count() === 1 && !$existing->has($family->first()->id)) return null;
            $choices = $family->map(fn ($p) => $existing->get($p->id) ?? [
                'choice_key' => 'component_'.$p->id, 'choice_kind' => '', 'component' => $p,
                'component_id' => $p->id, 'part_number' => $p->part_number, 'ipl_num' => $p->ipl_num,
                'label' => $p->name, 'manual_part_group_id' => null, 'manual_part_group_option_id' => null,
                'assy_part_number' => '', 'assy_ipl_num' => '', 'assembly_choices' => [],
            ])->values();
            return [
                'group_key' => 'part_choices_'.$family->pluck('id')->min(),
                'choices' => $choices,
            ];
        })->filter()->values();
    }
}
