<?php

namespace App\Services;

use App\Models\{ManualProcess, ManualProcessVersion, Process};
use Illuminate\Support\Facades\DB;

class ManualProcessVersions
{
    public static function printableIds(array $manualIds)
    {
        return ManualProcess::withTrashed()->whereIn('manual_id', $manualIds)->pluck('processes_id')
            ->merge(ManualProcessVersion::whereHas('manualProcess', fn ($q) => $q->whereIn('manual_id', $manualIds))->pluck('process_id'))
            ->unique()->values();
    }
    // The existing Process ID is version 1. Never rewrite text referenced by a WO.
    public static function record(ManualProcess $link, ?int $actor): void
    {
        $latest = $link->versions()->orderByDesc('version')->first();
        if ($latest && (int) $latest->process_id === (int) $link->processes_id
            && $latest->process_comment === $link->process_comment
            && $latest->requires_fig === (bool) $link->requires_fig
            && $latest->requires_zone === (bool) $link->requires_zone) return;
        $link->versions()->create([
            'process_id' => $link->processes_id, 'version' => ($latest?->version ?? 0) + 1,
            'process_comment' => $link->process_comment,
            'requires_fig' => (bool) $link->requires_fig, 'requires_zone' => (bool) $link->requires_zone,
            'created_by' => $actor,
        ]);
    }

    public static function edit(ManualProcess $link, array $values, ?int $actor): ManualProcess
    {
        return DB::transaction(function () use ($link, $values, $actor) {
            $current = ManualProcess::lockForUpdate()->findOrFail($link->id);
            abort_if((string) $current->updated_at !== (string) $link->updated_at
                || $current->processes_id !== $link->processes_id, 409, 'Process changed. Reload before editing.');
            self::record($current, $actor);
            $changed = isset($values['process']) && $values['process'] !== $current->process->process;
            foreach (['process_comment', 'requires_fig', 'requires_zone'] as $field) {
                if (array_key_exists($field, $values) && $values[$field] !== $current->{$field}) $changed = true;
            }
            // Requirements/comment belong to the version too: a new ZONE flag must
            // not invalidate a previously saved template using the old version.
            if ($changed) {
                $process = Process::create([
                    'process_names_id' => $current->process->process_names_id, 'process' => $values['process'] ?? $current->process->process,
                ]);
                $current->processes_id = $process->id;
                $current->unsetRelation('process');
            }
            $current->fill(collect($values)->only(['process_comment', 'requires_fig', 'requires_zone'])->all())->save();
            self::record($current, $actor);
            return $current->fresh(['process.process_name']);
        });
    }

    // Historical membership is only for saved steps, never for the new-process picker.
    public static function catalog(int $manualId, array $processIds)
    {
        $catalog = ManualProcess::withTrashed()->where('manual_id', $manualId)
            ->whereIn('processes_id', $processIds)->with('process')->get()->keyBy('processes_id');
        $history = ManualProcessVersion::whereIn('process_id', $processIds)
            ->whereHas('manualProcess', fn ($q) => $q->where('manual_id', $manualId))
            ->with('process')->orderBy('version')->get();
        foreach ($history as $version) {
            if ($catalog->has($version->process_id)) continue;
            $row = new ManualProcess([
                'manual_id' => $manualId, 'processes_id' => $version->process_id,
                'process_comment' => $version->process_comment,
                'requires_fig' => $version->requires_fig, 'requires_zone' => $version->requires_zone,
            ]);
            $row->setRelation('process', $version->process);
            $catalog->put($version->process_id, $row);
        }
        return $catalog;
    }

    public static function assertSelectable(int $manualId, int $processId, array $retained = []): void
    {
        $active = ManualProcess::where('manual_id', $manualId)->where('processes_id', $processId)->exists();
        abort_unless($active || (in_array($processId, $retained, true)
            && self::catalog($manualId, [$processId])->has($processId)), 422,
            'This specification is archived or belongs to another manual. Choose an active specification.');
    }
}
