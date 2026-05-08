<?php

namespace App\Console\Commands;

use App\Models\Tdr;
use App\Models\WorkorderUnitInspection;
use Illuminate\Console\Command;

class DeleteLegacyUnitInspectionTdrs extends Command
{
    // TODO(tdr-refactor): Remove this cleanup helper after legacy unit inspection TDR rows are no longer present in any deployed database.
    protected $signature = 'unit-inspections:delete-legacy-tdrs
        {--workorder= : Single workorder ID}
        {--write : Soft-delete legacy TDR rows. Default is dry-run}';

    protected $description = 'Soft-delete legacy unit inspection TDR rows after workorder_unit_inspections backfill.';

    public function handle(): int
    {
        $write = (bool) $this->option('write');
        $singleWorkorderId = $this->option('workorder');

        $query = Tdr::query()
            ->unitInspections()
            ->whereNotNull('workorder_id')
            ->whereNotNull('conditions_id')
            ->orderBy('workorder_id')
            ->orderBy('conditions_id')
            ->orderBy('id');

        if ($singleWorkorderId !== null && $singleWorkorderId !== '') {
            $query->where('workorder_id', (int) $singleWorkorderId);
        }

        $scanned = 0;
        $wouldDelete = 0;
        $deleted = 0;
        $skippedNoNewRow = 0;

        $query->chunkById(200, function ($tdrs) use ($write, &$scanned, &$wouldDelete, &$deleted, &$skippedNoNewRow): void {
            foreach ($tdrs as $tdr) {
                $scanned++;

                $hasNewRow = WorkorderUnitInspection::query()
                    ->where('workorder_id', (int) $tdr->workorder_id)
                    ->where('condition_id', (int) $tdr->conditions_id)
                    ->exists();

                if (! $hasNewRow) {
                    $skippedNoNewRow++;
                    $this->warn(sprintf(
                        '[WO %d] condition #%d source TDR #%d skipped: no workorder_unit_inspections row.',
                        (int) $tdr->workorder_id,
                        (int) $tdr->conditions_id,
                        (int) $tdr->id
                    ));
                    continue;
                }

                $wouldDelete++;
                $this->line(sprintf(
                    '[WO %d] condition #%d: delete legacy unit inspection TDR #%d',
                    (int) $tdr->workorder_id,
                    (int) $tdr->conditions_id,
                    (int) $tdr->id
                ));

                if ($write) {
                    $tdr->delete();
                    $deleted++;
                }
            }
        });

        $this->table(
            ['scanned', 'would delete', 'deleted', 'skipped no new row'],
            [[$scanned, $wouldDelete, $deleted, $skippedNoNewRow]]
        );

        if (! $write) {
            $this->warn('Dry run only. Run with --write to soft-delete legacy unit inspection TDR rows.');
        }

        return self::SUCCESS;
    }
}
