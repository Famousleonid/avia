<?php

namespace App\Console\Commands;

use App\Models\Instruction;
use App\Models\ProcessName;
use App\Models\TdrProcess;
use App\Models\Workorder;
use App\Services\WorkorderStdListProcessesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillStdListCarrierProcesses extends Command
{
    protected $signature = 'std-list:backfill-carriers
        {--workorder= : Single workorder ID}
        {--write : Persist changes (default is dry-run)}
        {--delete-legacy : Delete duplicate legacy STD rows after copying to carrier}';

    protected $description = 'Backfill legacy STD List tdr_processes into the workorder-level carrier rows used by mains';

    public function handle(): int
    {
        $overhaulId = Instruction::overhaulId();
        if (! $overhaulId) {
            $this->warn('Overhaul instruction not found.');

            return self::SUCCESS;
        }

        $write = (bool) $this->option('write');
        $deleteLegacy = (bool) $this->option('delete-legacy');
        $singleWorkorderId = $this->option('workorder');

        $service = app(WorkorderStdListProcessesService::class);
        $processNames = ProcessName::query()
            ->whereIn('name', array_values(WorkorderStdListProcessesService::NAME_BY_KEY))
            ->get()
            ->keyBy('name');

        if ($processNames->isEmpty()) {
            $this->warn('STD List process names not found.');

            return self::SUCCESS;
        }

        $query = Workorder::query()
            ->withoutGlobalScope('exclude_drafts')
            ->where('instruction_id', (int) $overhaulId);

        if ($singleWorkorderId !== null && $singleWorkorderId !== '') {
            $query->where('id', (int) $singleWorkorderId);
        }

        $workorders = $query->orderBy('id')->get();

        $changedRows = 0;
        $deletedRows = 0;
        $touchedWorkorders = 0;

        foreach ($workorders as $workorder) {
            $workorderChanged = false;

            foreach (WorkorderStdListProcessesService::NAME_BY_KEY as $key => $name) {
                $processName = $processNames->get($name);
                if (! $processName) {
                    continue;
                }

                $preferred = $service->findPreferredStdListProcessForWorkorder($workorder, (int) $processName->id);
                if (! $preferred) {
                    continue;
                }

                $carrierTdr = $service->ensureStdListCarrierTdr($workorder);
                $carrier = TdrProcess::query()->firstOrNew([
                    'tdrs_id' => $carrierTdr->id,
                    'process_names_id' => $processName->id,
                ]);

                $carrierExists = $carrier->exists;
                $payload = [
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
                ];

                $carrierOriginal = $carrierExists ? [
                    'repair_order' => $carrier->repair_order,
                    'vendor_id' => $carrier->vendor_id,
                    'date_start' => optional($carrier->date_start)?->format('Y-m-d'),
                    'date_finish' => optional($carrier->date_finish)?->format('Y-m-d'),
                    'ignore_row' => (bool) $carrier->ignore_row,
                ] : null;

                $preferredData = [
                    'id' => $preferred->id,
                    'tdr_id' => $preferred->tdrs_id,
                    'repair_order' => $preferred->repair_order,
                    'vendor_id' => $preferred->vendor_id,
                    'date_start' => optional($preferred->date_start)?->format('Y-m-d'),
                    'date_finish' => optional($preferred->date_finish)?->format('Y-m-d'),
                    'ignore_row' => (bool) $preferred->ignore_row,
                ];

                $carrier->fill($payload);
                $isDirty = ! $carrierExists || $carrier->isDirty();
                $willDeleteLegacy = $deleteLegacy && $preferred->id !== $carrier->id;

                if (! $isDirty && ! $willDeleteLegacy) {
                    continue;
                }

                $workorderChanged = true;
                $changedRows += $isDirty ? 1 : 0;

                $this->line(sprintf(
                    '[WO %d] %s: preferred #%d -> carrier %s',
                    (int) $workorder->id,
                    $name,
                    (int) $preferred->id,
                    $carrierExists ? ('#' . (int) $carrier->id) : 'new'
                ));
                $this->line('  preferred: ' . json_encode($preferredData));
                $this->line('  carrier before: ' . json_encode($carrierOriginal));

                if ($write) {
                    DB::transaction(function () use ($carrier, $preferred, $deleteLegacy, &$deletedRows): void {
                        $carrier->save();

                        if ($deleteLegacy && $preferred->id !== $carrier->id) {
                            TdrProcess::query()
                                ->where('id', $preferred->id)
                                ->delete();
                            $deletedRows++;
                        }
                    });
                }
            }

            if ($workorderChanged) {
                $touchedWorkorders++;
            }
        }

        $mode = $write ? 'WRITE' : 'DRY-RUN';
        $this->info(sprintf(
            '%s complete. Workorders touched: %d. Carrier rows changed: %d. Legacy rows deleted: %d.',
            $mode,
            $touchedWorkorders,
            $changedRows,
            $deletedRows
        ));

        return self::SUCCESS;
    }
}
