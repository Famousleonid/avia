<?php

namespace App\Console\Commands;

use App\Models\ProcessName;
use App\Models\TdrProcess;
use App\Models\Workorder;
use App\Models\WorkorderStdProcess;
use App\Services\WorkorderStdListProcessesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillWorkorderStdProcesses extends Command
{
    protected $signature = 'std-list:backfill-workorder-processes
        {--workorder= : Single workorder ID}
        {--write : Persist changes. Default is dry-run}
        {--overwrite : Re-copy over existing workorder_std_processes rows}';

    protected $description = 'Copy legacy STD List tdr_processes into direct workorder_std_processes rows.';

    public function handle(): int
    {
        $write = (bool) $this->option('write');
        $overwrite = (bool) $this->option('overwrite');
        $singleWorkorderId = $this->option('workorder');

        $processNamesByName = ProcessName::query()
            ->whereIn('name', array_values(WorkorderStdListProcessesService::NAME_BY_KEY))
            ->get()
            ->keyBy('name');

        if ($processNamesByName->isEmpty()) {
            $this->warn('STD List process names not found.');

            return self::SUCCESS;
        }

        $orphanCount = TdrProcess::query()
            ->whereNull('tdrs_id')
            ->whereIn('process_names_id', $processNamesByName->pluck('id'))
            ->count();

        if ($orphanCount > 0) {
            $this->warn(sprintf(
                '%d STD List tdr_process rows have NULL tdrs_id and cannot be assigned to a workorder automatically.',
                $orphanCount
            ));
        }

        $workorderIds = TdrProcess::query()
            ->join('tdrs', 'tdrs.id', '=', 'tdr_processes.tdrs_id')
            ->whereIn('tdr_processes.process_names_id', $processNamesByName->pluck('id'))
            ->when($singleWorkorderId !== null && $singleWorkorderId !== '', function ($query) use ($singleWorkorderId): void {
                $query->where('tdrs.workorder_id', (int) $singleWorkorderId);
            })
            ->distinct()
            ->orderBy('tdrs.workorder_id')
            ->pluck('tdrs.workorder_id');

        $service = app(WorkorderStdListProcessesService::class);
        $scanned = 0;
        $wouldChange = 0;
        $written = 0;
        $skippedExisting = 0;

        foreach ($workorderIds as $workorderId) {
            $workorder = Workorder::query()
                ->withoutGlobalScope('exclude_drafts')
                ->find((int) $workorderId);

            if (! $workorder) {
                continue;
            }

            foreach (WorkorderStdListProcessesService::NAME_BY_KEY as $stdType => $name) {
                $processName = $processNamesByName->get($name);
                if (! $processName) {
                    continue;
                }

                $preferred = $service->findPreferredStdListProcessForWorkorder($workorder, (int) $processName->id);
                if (! $preferred) {
                    continue;
                }

                $scanned++;
                $target = WorkorderStdProcess::query()->firstOrNew([
                    'workorder_id' => (int) $workorder->id,
                    'process_name_id' => (int) $processName->id,
                ]);

                if ($target->exists && ! $overwrite) {
                    $skippedExisting++;
                    continue;
                }

                $target->fill($this->payloadFromPreferred($preferred, $stdType));

                if ($target->exists && ! $target->isDirty()) {
                    $skippedExisting++;
                    continue;
                }

                $wouldChange++;
                $this->line(sprintf(
                    '[WO %d] %s: source tdr_process #%d -> workorder_std_process %s',
                    (int) $workorder->id,
                    $name,
                    (int) $preferred->id,
                    $target->exists ? ('#'.(int) $target->id) : 'new'
                ));

                if ($write) {
                    DB::transaction(function () use ($target, &$written): void {
                        $target->save();
                        $written++;
                    });
                }
            }
        }

        $this->table(
            ['scanned', 'would change', 'written', 'skipped existing', 'orphan null tdrs_id'],
            [[$scanned, $wouldChange, $written, $skippedExisting, $orphanCount]]
        );

        if (! $write) {
            $this->warn('Dry run only. Run with --write to persist workorder_std_processes rows.');
        }

        return self::SUCCESS;
    }

    private function payloadFromPreferred(TdrProcess $preferred, string $stdType): array
    {
        return [
            'std_type' => $stdType,
            'source_tdr_id' => $preferred->tdrs_id,
            'source_tdr_process_id' => $preferred->id,
            'processes' => $preferred->processes,
            'description' => $preferred->description,
            'notes' => $preferred->notes,
            'repair_order' => $preferred->repair_order,
            'vendor_id' => $preferred->vendor_id,
            'date_start' => $preferred->date_start,
            'date_start_user_id' => $preferred->date_start_user_id,
            'date_finish' => $preferred->date_finish,
            'date_finish_user_id' => $preferred->date_finish_user_id,
            'date_promise' => $preferred->date_promise,
            'ignore_row' => (bool) $preferred->ignore_row,
            'user_id' => $preferred->user_id,
        ];
    }
}
