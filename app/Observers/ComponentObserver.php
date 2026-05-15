<?php

namespace App\Observers;

use App\Models\Component;
use App\Models\StdProcess;
use App\Services\WorkorderStdProcessItemsService;

class ComponentObserver
{
    private const SNAPSHOT_FIELDS = [
        'manual_id',
        'ipl_num',
        'part_number',
        'name',
        'units_assy',
        'eff_code',
        'ndt_list',
        'cad_list',
        'stress_relief_list',
        'paint_list',
    ];

    public function saved(Component $component): void
    {
        $originalManualId = (int) ($component->getOriginal('manual_id') ?: 0);

        if (! $component->manual_id || trim((string) $component->ipl_num) === '') {
            StdProcess::query()
                ->where('component_id', $component->id)
                ->get()
                ->each
                ->delete();

            if ($originalManualId > 0 && $this->shouldInvalidateSnapshots($component)) {
                app(WorkorderStdProcessItemsService::class)->invalidateForManual($originalManualId);
            }
            return;
        }

        foreach (StdProcess::validStdValues() as $std) {
            $flagColumn = $this->flagColumnForStd($std);
            if (! $component->wasRecentlyCreated
                && ! $component->wasChanged($flagColumn)
                && ! $this->wasPartIdentityChanged($component)
                && ! $this->wasPartStdContentChanged($component)) {
                continue;
            }

            if ((bool) $component->{$flagColumn}) {
                $this->syncStdRow($component, $std);
            } else {
                $this->deleteStdRow($component, $std);
            }
        }

        if ($this->shouldInvalidateSnapshots($component)) {
            if ($originalManualId > 0 && $originalManualId !== (int) $component->manual_id) {
                app(WorkorderStdProcessItemsService::class)->invalidateForManual($originalManualId);
            }
            app(WorkorderStdProcessItemsService::class)->invalidateForManual((int) $component->manual_id);
        }
    }

    public function deleted(Component $component): void
    {
        if ($component->manual_id) {
            app(WorkorderStdProcessItemsService::class)->invalidateForManual((int) $component->manual_id);
        }
    }

    protected function syncStdRow(Component $component, string $std): void
    {
        $stdProcess = StdProcess::query()->firstOrNew([
            'component_id' => $component->id,
            'std' => $std,
        ]);

        if (! $stdProcess->exists) {
            $stdProcess->process = $this->defaultProcess((int) $component->manual_id, $std);
        }

        $stdProcess->manual_id = $component->manual_id;
        $stdProcess->qty = max(1, (int) ($component->units_assy ?? 1));
        $stdProcess->eff_code = StdProcess::normalizeEffCodeForStorage($component->eff_code);
        $stdProcess->save();
    }

    protected function deleteStdRow(Component $component, string $std): void
    {
        StdProcess::query()
            ->where('component_id', $component->id)
            ->where('std', $std)
            ->get()
            ->each
            ->delete();
    }

    protected function defaultProcess(int $manualId, string $std): string
    {
        $values = StdProcess::processPicklistValuesForManual($manualId, $std);

        return (string) ($values[0] ?? '1');
    }

    protected function flagColumnForStd(string $std): string
    {
        return match ($std) {
            StdProcess::STD_NDT => 'ndt_list',
            StdProcess::STD_CAD => 'cad_list',
            StdProcess::STD_STRESS => 'stress_relief_list',
            StdProcess::STD_PAINT => 'paint_list',
            default => throw new \InvalidArgumentException("Invalid std type: {$std}"),
        };
    }

    protected function shouldInvalidateSnapshots(Component $component): bool
    {
        if ($component->wasRecentlyCreated) {
            return $this->hasAnyStdFlag($component);
        }

        foreach (self::SNAPSHOT_FIELDS as $field) {
            if ($component->wasChanged($field)) {
                return true;
            }
        }

        return false;
    }

    protected function hasAnyStdFlag(Component $component): bool
    {
        foreach (StdProcess::validStdValues() as $std) {
            if ((bool) $component->{$this->flagColumnForStd($std)}) {
                return true;
            }
        }

        return false;
    }

    protected function wasPartIdentityChanged(Component $component): bool
    {
        return $component->wasChanged('manual_id')
            || $component->wasChanged('ipl_num')
            || $component->wasChanged('part_number');
    }

    protected function wasPartStdContentChanged(Component $component): bool
    {
        return $component->wasChanged('name')
            || $component->wasChanged('units_assy')
            || $component->wasChanged('eff_code');
    }
}
