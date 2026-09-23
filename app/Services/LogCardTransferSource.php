<?php

namespace App\Services;

use App\Models\{Code, Component, LogCard, Transfer, Workorder};
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class LogCardTransferSource
{
    public function target(Workorder $workorder, string $rowKey): object
    {
        $row = app(WorkorderPartsList::class)->rows($workorder)->firstWhere('id', $rowKey);
        if (!$row || $row->crossed_out) throw ValidationException::withMessages(['row_key' => 'This KIT/PRL part is no longer available. Reload Parts.']);
        return $row;
    }

    public function choices(Workorder $target, object $part, Workorder $source): Collection
    {
        if ($source->id === $target->id) throw ValidationException::withMessages(['source' => 'Source and receiving workorders must differ.']);
        $ids = $part->options->reject(fn ($o) => !empty($o['crossed_out']))->pluck('component_id');
        if ($ids->isEmpty()) $ids = collect([$part->component->id]);
        $targets = Component::whereIn('id', $ids->filter())->get();
        $manualIds = $targets->pluck('manual_id')->unique();
        if ($manualIds->intersect(collect($source->usedManualIds()))->isEmpty()) {
            throw ValidationException::withMessages(['source' => 'Source and receiving parts must belong to the same manual.']);
        }
        $wanted = $part->options->reject(fn ($o) => !empty($o['crossed_out']))->pluck('part_number');
        if ($wanted->isEmpty()) $wanted = collect([$part->component->part_number]);
        $wanted = $wanted->map(fn ($pn) => strtoupper(trim((string) $pn)));
        $card = LogCard::where('workorder_id', $source->id)->first();
        if (!$card) return collect();
        $raw = is_array($card->component_data) ? $card->component_data : (json_decode($card->component_data ?? '[]', true) ?: []);
        $rows = app(LogCardAssemblyIdentity::class)->cleanRows($raw, $source);
        $components = Component::whereIn('id', collect($rows)->pluck('component_id')->filter())->with('manual:id,number')->get()->keyBy('id');
        $unavailableCodes = Code::where('requires_destruction_cert', true)->orWhere('name', Code::NAME_MISSING)->pluck('id');
        $transfers = Transfer::where('workorder_source', $source->id)->get();
        $result = collect();
        foreach ($rows as $index => $row) {
            if (!is_array($row) || ($row['row_type'] ?? '') === 'manual' || (isset($row['included']) && !(bool) $row['included'])) continue;
            $component = $components->get((int) ($row['component_id'] ?? 0));
            if (!$component || !$manualIds->contains($component->manual_id) || $unavailableCodes->contains((int) ($row['reason'] ?? 0))) continue;
            $serial = trim((string) ($row['serial_number'] ?? ''));
            $quantity = $serial !== '' ? 1 : max(1, (int) ($row['units_assy'] ?? 1));
            $sourceKey = hash('sha256', json_encode($serial !== ''
                ? [$card->id, $component->manual_id, strtoupper(trim($component->part_number)), $serial]
                : [$card->id, $component->id, $row['unit_index'] ?? '', $row['ipl_group'] ?? '']));
            $used = $transfers->filter(fn ($t) => $t->source_log_row_key === $sourceKey
                || (!$t->source_log_row_key && (int) $t->component_id === (int) $component->id && (string) $t->component_sn === $serial));
            $available = max(0, $quantity - $used->sum(fn ($t) => max(1, (int) ($t->qty ?? 1))));
            $identities = [['part_number' => $component->part_number, 'ipl' => $component->ipl_num, 'sn' => $serial]];
            if (!empty($row['assy_part_number']) && !empty($row['assy_serial_number'])) {
                $identities[] = ['part_number' => $row['assy_part_number'], 'ipl' => $row['assy_ipl_num'] ?? $component->ipl_num, 'sn' => $row['assy_serial_number']];
            }
            foreach ($identities as $identity) {
                if (!$wanted->contains(strtoupper(trim((string) $identity['part_number'])))) continue;
                $token = hash('sha256', json_encode([$sourceKey, $row, $identity]));
                $result->push([
                    'token' => $token, 'source_key' => $sourceKey, 'log_card_id' => $card->id,
                    'component_id' => $component->id, 'manual_id' => $component->manual_id,
                    'manual' => $component->manual?->number, 'part_number' => $identity['part_number'],
                    'ipl' => $identity['ipl'], 'name' => $component->name, 'serial_number' => $identity['sn'],
                    'available' => $available, 'required' => (int) $part->qty,
                    'can_transfer' => $available >= (int) $part->qty,
                    'reason_id' => $row['reason'] ?? null,
                ]);
            }
        }
        return $result->unique('token')->values();
    }
}
