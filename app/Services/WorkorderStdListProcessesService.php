<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Instruction;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Workorder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class WorkorderStdListProcessesService
{
    public const CARRIER_DESCRIPTION = 'STD List carrier';

    public const NAME_BY_KEY = [
        'ndt' => 'STD NDT List',
        'cad' => 'STD CAD List',
        'stress' => 'STD Stress relief List',
        'paint' => 'STD Paint List',
    ];

    public function ensureStdListCarrierTdr(Workorder $workorder): Tdr
    {
        $wid = (int) $workorder->id;

        $tdr = Tdr::query()
            ->where('workorder_id', $wid)
            ->whereNull('component_id')
            ->where('description', self::CARRIER_DESCRIPTION)
            ->orderBy('id')
            ->first();

        if ($tdr === null) {
            $tdr = Tdr::query()
                ->where('workorder_id', $wid)
                ->whereNull('component_id')
                ->whereNull('codes_id')
                ->whereNull('conditions_id')
                ->whereNull('necessaries_id')
                ->where(function ($query): void {
                    $query->whereNull('description')->orWhere('description', '');
                })
                ->orderBy('id')
                ->first();
        }

        if ($tdr !== null) {
            $tdr->fill($this->carrierTdrDefaults());
            if ($tdr->isDirty()) {
                $tdr->save();
            }

            return $tdr;
        }

        return Tdr::create($this->carrierTdrDefaults() + [
            'workorder_id' => $wid,
            'component_id' => null,
        ]);
    }

    public function isStdListCarrierTdr(?Tdr $tdr): bool
    {
        if (! $tdr || $tdr->component_id !== null) {
            return false;
        }

        if ((string) ($tdr->description ?? '') === self::CARRIER_DESCRIPTION) {
            return true;
        }

        return $tdr->codes_id === null
            && $tdr->conditions_id === null
            && $tdr->necessaries_id === null
            && trim((string) ($tdr->description ?? '')) === '';
    }

    public function hasMeaningfulState(TdrProcess $row): bool
    {
        return ! empty($row->date_start)
            || ! empty($row->date_finish)
            || trim((string) ($row->repair_order ?? '')) !== ''
            || ! empty($row->vendor_id)
            || (bool) ($row->ignore_row ?? false);
    }

    /**
     * @return EloquentCollection<int, TdrProcess>
     */
    public function stdListProcessRowsForWorkorder(Workorder $workorder, int $processNameId): EloquentCollection
    {
        return TdrProcess::query()
            ->where('process_names_id', $processNameId)
            ->whereHas('tdr', function ($query) use ($workorder): void {
                $query->where('workorder_id', (int) $workorder->id);
            })
            ->with('tdr:id,workorder_id,component_id,codes_id,conditions_id,necessaries_id,description')
            ->get();
    }

    /**
     * @return Collection<string, TdrProcess>|null
     */
    public function resolveForWorkorder(Workorder $workorder): ?Collection
    {
        $overhaulId = Instruction::overhaulId();
        if (! $overhaulId || (int) $workorder->instruction_id !== (int) $overhaulId) {
            return null;
        }

        $processNames = ProcessName::query()
            ->whereIn('name', array_values(self::NAME_BY_KEY))
            ->get()
            ->keyBy('name');

        $out = collect();
        $carrierTdr = null;
        foreach (self::NAME_BY_KEY as $key => $name) {
            $pn = $processNames->get($name);
            if (! $pn) {
                continue;
            }

            $tp = $this->findPreferredSafeStdListProcessForWorkorder($workorder, (int) $pn->id);
            if (! $tp) {
                $carrierTdr ??= $this->ensureStdListCarrierTdr($workorder);
                $tp = TdrProcess::firstOrCreate(
                    [
                        'tdrs_id' => $carrierTdr->id,
                        'process_names_id' => $pn->id,
                    ],
                    []
                );
            }

            $tp->load(['processName', 'updatedBy', 'dateStartUpdatedBy', 'dateFinishUpdatedBy', 'vendor:id,name']);
            $out->put($key, $tp);
        }

        return $out->isEmpty() ? null : $out;
    }

    public function findPreferredStdListProcessForWorkorder(Workorder $workorder, int $processNameId): ?TdrProcess
    {
        /** @var EloquentCollection<int, TdrProcess> $rows */
        $rows = $this->stdListProcessRowsForWorkorder($workorder, $processNameId);

        if ($rows->isEmpty()) {
            return null;
        }

        return $rows
            ->sortByDesc(function (TdrProcess $row): array {
                $isCarrier = $this->isStdListCarrierTdr($row->tdr);
                $hasMeaningfulState = $this->hasMeaningfulState($row);
                $isComponentLegacy = $row->tdr && $row->tdr->component_id !== null;
                $isInvalidWorkorderLevel = $row->tdr
                    && $row->tdr->component_id === null
                    && ! $isCarrier;

                return [
                    $isCarrier && $hasMeaningfulState ? 5 : 0,
                    $isComponentLegacy && $hasMeaningfulState ? 4 : 0,
                    $isInvalidWorkorderLevel && $hasMeaningfulState ? 3 : 0,
                    $isCarrier ? 2 : 0,
                    $hasMeaningfulState ? 1 : 0,
                    optional($row->updated_at)?->getTimestamp() ?? 0,
                    (int) $row->id,
                ];
            })
            ->first();
    }

    public function findPreferredSafeStdListProcessForWorkorder(Workorder $workorder, int $processNameId): ?TdrProcess
    {
        $preferred = $this->findPreferredStdListProcessForWorkorder($workorder, $processNameId);

        return $preferred ? $this->copyStateToCarrierIfNeeded($workorder, $preferred) : null;
    }

    public function deleteDuplicateStdListProcesses(Workorder $workorder, int $processNameId, int $keepId): int
    {
        $deleted = 0;

        $this->stdListProcessRowsForWorkorder($workorder, $processNameId)
            ->reject(fn (TdrProcess $row): bool => (int) $row->id === $keepId)
            ->each(function (TdrProcess $row) use (&$deleted): void {
                $row->delete();
                $deleted++;
            });

        return $deleted;
    }

    private function carrierTdrDefaults(): array
    {
        return [
            'description' => self::CARRIER_DESCRIPTION,
            'codes_id' => null,
            'conditions_id' => null,
            'necessaries_id' => null,
            'use_tdr' => true,
            'use_process_forms' => false,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'qty' => 1,
        ];
    }

    private function copyStateToCarrierIfNeeded(Workorder $workorder, TdrProcess $preferred): TdrProcess
    {
        $tdr = $preferred->tdr;
        $isInvalidWorkorderLevel = $tdr
            && $tdr->component_id === null
            && ! $this->isStdListCarrierTdr($tdr);

        if (! $isInvalidWorkorderLevel) {
            return $preferred;
        }

        $carrierTdr = $this->ensureStdListCarrierTdr($workorder);
        $carrier = TdrProcess::query()->firstOrNew([
            'tdrs_id' => $carrierTdr->id,
            'process_names_id' => $preferred->process_names_id,
        ]);

        if (! $carrier->exists || ! $this->hasMeaningfulState($carrier)) {
            $carrier->fill([
                'processes' => $carrier->processes ?? $preferred->processes,
                'description' => $carrier->description ?? $preferred->description,
                'notes' => $carrier->notes ?? $preferred->notes,
                'repair_order' => $preferred->repair_order,
                'vendor_id' => $preferred->vendor_id,
                'date_start' => $preferred->date_start,
                'date_finish' => $preferred->date_finish,
                'date_start_user_id' => $preferred->date_start_user_id,
                'date_finish_user_id' => $preferred->date_finish_user_id,
                'ignore_row' => (bool) $preferred->ignore_row,
                'user_id' => $preferred->user_id,
            ]);
            $carrier->save();
        }

        return $carrier;
    }
}
