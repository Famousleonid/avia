<?php

namespace App\Console\Commands;

use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\WorkorderStdProcess;
use App\Services\WorkorderStdListProcessesService;
use Illuminate\Console\Command;

class DeleteLegacyStdListTdrProcesses extends Command
{
    // TODO(tdr-refactor): Remove this cleanup helper after legacy STD tdr_process rows are no longer present in any deployed database.
    protected $signature = 'std-list:delete-legacy-tdr-processes
        {--workorder= : Single workorder ID}
        {--include-orphans : Delete STD List tdr_processes with NULL tdrs_id too}
        {--write : Delete legacy rows. Default is dry-run}';

    protected $description = 'Delete legacy STD List tdr_processes and soft-delete empty STD carrier TDR rows.';

    public function handle(): int
    {
        $write = (bool) $this->option('write');
        $includeOrphans = (bool) $this->option('include-orphans');
        $singleWorkorderId = $this->option('workorder');

        $processNameIds = ProcessName::query()
            ->whereIn('name', array_values(WorkorderStdListProcessesService::NAME_BY_KEY))
            ->pluck('id');

        if ($processNameIds->isEmpty()) {
            $this->warn('STD List process names not found.');

            return self::SUCCESS;
        }

        $query = TdrProcess::query()
            ->whereIn('process_names_id', $processNameIds)
            ->with('tdr:id,workorder_id,tdr_type');

        if ($singleWorkorderId !== null && $singleWorkorderId !== '') {
            $query->whereHas('tdr', fn ($q) => $q->where('workorder_id', (int) $singleWorkorderId));
        }

        $scanned = 0;
        $wouldDelete = 0;
        $deleted = 0;
        $skippedNoNewRow = 0;
        $orphanRows = 0;

        $query->chunkById(200, function ($rows) use ($write, $includeOrphans, &$scanned, &$wouldDelete, &$deleted, &$skippedNoNewRow, &$orphanRows): void {
            foreach ($rows as $row) {
                $scanned++;
                $workorderId = (int) ($row->tdr?->workorder_id ?? 0);

                if ($workorderId <= 0) {
                    $orphanRows++;
                    if (! $includeOrphans) {
                        $skippedNoNewRow++;
                        continue;
                    }
                } else {
                    $hasNewRow = WorkorderStdProcess::query()
                        ->where('workorder_id', $workorderId)
                        ->where('process_name_id', (int) $row->process_names_id)
                        ->exists();

                    if (! $hasNewRow) {
                        $skippedNoNewRow++;
                        $this->warn(sprintf(
                            '[WO %d] legacy STD tdr_process #%d skipped: no workorder_std_process row.',
                            $workorderId,
                            (int) $row->id
                        ));
                        continue;
                    }
                }

                $wouldDelete++;
                $this->line(sprintf(
                    '[WO %s] delete legacy STD tdr_process #%d',
                    $workorderId > 0 ? (string) $workorderId : 'orphan',
                    (int) $row->id
                ));

                if ($write) {
                    $row->delete();
                    $deleted++;
                }
            }
        });

        $carrierScanned = 0;
        $carrierWouldDelete = 0;
        $carrierDeleted = 0;

        $carrierQuery = Tdr::query()
            ->stdListCarriers()
            ->doesntHave('tdrProcesses')
            ->orderBy('id');

        if ($singleWorkorderId !== null && $singleWorkorderId !== '') {
            $carrierQuery->where('workorder_id', (int) $singleWorkorderId);
        }

        $carrierQuery->chunkById(200, function ($carriers) use ($write, &$carrierScanned, &$carrierWouldDelete, &$carrierDeleted): void {
            foreach ($carriers as $carrier) {
                $carrierScanned++;
                $carrierWouldDelete++;
                $this->line(sprintf(
                    '[WO %d] soft-delete empty STD carrier TDR #%d',
                    (int) $carrier->workorder_id,
                    (int) $carrier->id
                ));

                if ($write) {
                    $carrier->delete();
                    $carrierDeleted++;
                }
            }
        });

        $this->table(
            ['process scanned', 'process would delete', 'process deleted', 'orphan rows', 'skipped no new row', 'carrier scanned', 'carrier would delete', 'carrier deleted'],
            [[$scanned, $wouldDelete, $deleted, $orphanRows, $skippedNoNewRow, $carrierScanned, $carrierWouldDelete, $carrierDeleted]]
        );

        if (! $write) {
            $this->warn('Dry run only. Run with --write to delete legacy STD List tdr_processes.');
        }

        return self::SUCCESS;
    }
}
