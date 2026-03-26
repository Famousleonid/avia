<?php

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
     * @return Collection<string, TdrProcess>|null
     */
    public function resolveForWorkorder(Workorder $workorder): ?Collection
    {
        $overhaulId = Instruction::overhaulId();
        if (!$overhaulId || (int) $workorder->instruction_id !== (int) $overhaulId) {
            return null;
        }

        $tdr = Tdr::where('workorder_id', $workorder->id)->orderBy('id')->first();
        if (!$tdr) {
            $tdr = Tdr::create([
                'workorder_id' => $workorder->id,
                'use_tdr' => true,
                'use_process_forms' => false,
                'serial_number' => 'NSN',
                'assy_serial_number' => ' ',
                'qty' => 1,
            ]);
        }

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
