<?php

namespace App\Services;

use App\Models\{ProcessName, UserUiSetting};
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProcessVendorPreferences
{
    public function forUser(int $userId): array
    {
        $labels = ['stress' => 'Stress relief', 'ndt' => 'NDT', 'cad' => 'CAD', 'paint' => 'Paint'];
        $options = ProcessName::orderBy('name')->get()->map(function ($process) use ($labels) {
            $std = array_search($process->identityName(), WorkorderStdListProcessesService::NAME_BY_KEY, true);
            $name = $std !== false ? $labels[$std] : $process->name;
            return ['name' => $name, 'member' => $process->name, 'process' => $name,
                'legacy_ndt' => ProcessVendorQueue::groupName($name, $process->process_sheet_name) === 'NDT',
                'default' => !ProcessVendorQueue::hiddenByDefault($name, $process->process_sheet_name)
                    && ($std !== false || $process->show_in_process_picker !== false)];
        })->push(['name' => 'Traveler', 'member' => 'Traveler', 'process' => 'Traveler', 'legacy_ndt' => false, 'default' => true])
            ->groupBy('name')->map(fn ($group, $name) => ['name' => $name,
                'members' => $group->pluck('member')->unique()->implode(', '), 'processes' => $group->pluck('process')->unique()->sort()->values()->all(),
                'legacy_ndt' => $group->contains('legacy_ndt', true), 'default' => $group->contains('default', true)])
            ->sortKeys()->values();
        $saved = UserUiSetting::where('user_id', $userId)->where('scope', 'process-vendor.index')->where('key', 'visible_processes')->first()?->value;
        $version = UserUiSetting::where('user_id', $userId)->where('scope', 'process-vendor.index')->where('key', 'process_catalog_version')->first()?->value;
        $legacyNdt = (int) $version < 2 ? $options->where('legacy_ndt', true)->pluck('name')->all() : [];
        $expandLegacy = fn (array $names) => array_values(array_unique(array_merge($names, in_array('NDT', $names, true) ? $legacyNdt : [])));
        if (is_array($saved)) $saved = $expandLegacy($saved);
        $visible = is_array($saved) ? $options->whereIn('name', $saved)->pluck('name') : $options->where('default', true)->pluck('name');
        $savedGroups = UserUiSetting::where('user_id', $userId)->where('scope', 'process-vendor.index')->where('key', 'process_groups')->first()?->value;
        $groups = collect(is_array($savedGroups) ? $savedGroups : [])
            ->filter(fn ($group) => is_array($group) && is_string($group['name'] ?? null) && trim($group['name']) !== '' && is_array($group['members'] ?? null))
            ->map(fn ($group) => [
            'name' => trim($group['name']), 'enabled' => (bool) ($group['enabled'] ?? true),
            'members' => array_values(array_intersect($expandLegacy(array_filter($group['members'], 'is_string')), $options->pluck('name')->all()))
        ])->filter(fn ($group) => count($group['members']) > 0)->values();
        $mapping = [];
        foreach ($groups->where('enabled', true) as $group) foreach ($group['members'] as $member) $mapping[$member] = $group['name'];
        $enabled = $visible->reject(fn ($name) => isset($mapping[$name]))
            ->merge($groups->where('enabled', true)->pluck('name'))->unique()->sort()->values();
        $defaults = config('process_vendor.defaults', ['visible' => [], 'groups' => []]);
        $known = $options->pluck('name')->all();
        $defaults = [
            'visible' => array_values(array_intersect($defaults['visible'], $known)),
            'groups' => collect($defaults['groups'])->map(fn ($group) => [
                'name' => $group['name'], 'enabled' => (bool) $group['enabled'],
                'members' => array_values(array_intersect($group['members'], $known)),
            ])->filter(fn ($group) => count($group['members']) > 0)->values()->all(),
        ];
        return ['options' => $options, 'visible' => $visible->values(), 'groups' => $groups, 'enabled' => $enabled, 'defaults' => $defaults];
    }

    /** Presentation-only grouping: keep readiness fingerprints and original process names intact. */
    public function apply(Collection $rows, array $preferences, bool $includeHidden = false): Collection
    {
        // Split the legacy cached NDT bucket only for presentation. Fingerprints and RO snapshots stay intact.
        $rows = $rows->map(function ($row) {
            $row['process_group'] = $row['process_name'];
            return $row;
        });
        $mapping = [];
        foreach ($preferences['groups'] as $group) {
            if ($group['enabled'] ?? true) foreach ($group['members'] as $member) $mapping[$member] = $group['name'];
        }
        $visible = collect($preferences['visible'])->merge(array_keys($mapping));
        return ($includeHidden ? $rows : $rows->whereIn('process_group', $visible))->map(function ($row) use ($mapping) {
            $row['base_process_group'] = $row['process_group'];
            $row['process_group'] = $mapping[$row['process_group']] ?? $row['process_group'];
            return $row;
        })->values();
    }

    public function validateGroups(array $groups, array $options): array
    {
        $names = []; $assigned = [];
        foreach ($groups as &$group) {
            $group['name'] = preg_replace('/\s+/u', ' ', trim($group['name']));
            $key = mb_strtolower($group['name']);
            if ($key === '' || isset($names[$key])) {
                throw ValidationException::withMessages(['groups' => 'Each group needs a different, nonempty name.']);
            }
            $names[$key] = true;
            foreach ($options as $option) {
                if (mb_strtolower(trim($option)) === $key && !in_array($option, $group['members'], true)) {
                    throw ValidationException::withMessages(['groups' => 'Include '.$option.' in the group with that name, or choose another group name.']);
                }
            }
            foreach ($group['members'] as $member) {
                if (isset($assigned[$member])) throw ValidationException::withMessages(['groups' => $member.' can belong to only one group.']);
                $assigned[$member] = true;
            }
        }
        unset($group);
        return $groups;
    }
}
