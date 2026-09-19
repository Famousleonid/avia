<?php

namespace App\Services;

use App\Models\Component;

class LogCardAssemblyIdentity
{
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

    /** Correct only inherited BOM identities; keep serials and explicit assembly selections. */
    public function cleanRows(array $rows): array
    {
        $components = Component::with('assemblies')->whereIn('id', collect($rows)
            ->filter(fn ($row): bool => is_array($row) && ($row['manual_part_group_choice'] ?? '') === 'component')
            ->pluck('component_id')->filter()->unique())->get()->keyBy('id');

        foreach ($rows as &$row) {
            if (! is_array($row) || ($row['manual_part_group_choice'] ?? '') !== 'component') {
                continue;
            }
            $component = $components->get((int) ($row['component_id'] ?? 0));
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
