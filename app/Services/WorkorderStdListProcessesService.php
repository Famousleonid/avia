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
    public const NAME_BY_KEY = [
        'ndt' => 'STD NDT List',
        'cad' => 'STD CAD List',
        'stress' => 'STD Stress relief List',
        'paint' => 'STD Paint List',
    ];

    /**
     * TDR-носитель процессов STD List: без привязки к детали (component_id = null),
     * чтобы при просмотре процессов по компоненту не отображались четыре строки уровня WO.
     * Старые данные: смена инструкции Repair → Overhaul пересоздаёт STD (см. WorkorderController::update).
     */
    public function ensureStdListCarrierTdr(Workorder $workorder): Tdr
    {
        $wid = (int) $workorder->id;

        $tdr = Tdr::query()
            ->where('workorder_id', $wid)
            ->whereNull('component_id')
            ->orderBy('id')
            ->first();

        if ($tdr !== null) {
            return $tdr;
        }

        return Tdr::create([
            'workorder_id' => $wid,
            'component_id' => null,
            'use_tdr' => true,
            'use_process_forms' => false,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'qty' => 1,
        ]);
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

            $tp = $this->findPreferredStdListProcessForWorkorder($workorder, (int) $pn->id);
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
        $rows = TdrProcess::query()
            ->where('process_names_id', $processNameId)
            ->whereHas('tdr', function ($query) use ($workorder) {
                $query->where('workorder_id', (int) $workorder->id);
            })
            ->with('tdr:id,workorder_id,component_id')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return $rows
            ->sortByDesc(function (TdrProcess $row): array {
                $hasMeaningfulState =
                    ! empty($row->date_start)
                    || ! empty($row->date_finish)
                    || trim((string) ($row->repair_order ?? '')) !== ''
                    || ! empty($row->vendor_id)
                    || (bool) ($row->ignore_row ?? false);

                $isWorkorderLevel = $row->tdr && $row->tdr->component_id === null;

                return [
                    $hasMeaningfulState ? 1 : 0,
                    $isWorkorderLevel ? 1 : 0,
                    optional($row->updated_at)?->getTimestamp() ?? 0,
                    (int) $row->id,
                ];
            })
            ->first();
    }
}
