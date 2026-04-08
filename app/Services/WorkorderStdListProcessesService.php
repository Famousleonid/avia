<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Instruction;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Workorder;
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

        $tdr = $this->ensureStdListCarrierTdr($workorder);

        $out = collect();
        foreach (self::NAME_BY_KEY as $key => $name) {
            $pn = ProcessName::where('name', $name)->first();
            if (!$pn) {
                continue;
            }
            $tp = TdrProcess::firstOrCreate(
                [
                    'tdrs_id' => $tdr->id,
                    'process_names_id' => $pn->id,
                ],
                []
            );
            $tp->load(['processName', 'updatedBy']);
            $out->put($key, $tp);
        }

        return $out->isEmpty() ? null : $out;
    }
}
