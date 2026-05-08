<?php

namespace App\Console\Commands;

use App\Models\Tdr;
use App\Models\WorkorderUnitInspection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillWorkorderUnitInspections extends Command
{
    // TODO(tdr-refactor): Remove this migration helper after production has been backfilled and verified.
    protected $signature = 'unit-inspections:backfill-workorder-inspections
        {--workorder= : Single workorder ID}
        {--with-trashed : Include soft-deleted source TDR rows}
        {--write : Persist changes. Default is dry-run}
        {--overwrite : Re-copy over existing workorder_unit_inspections rows}';

    protected $description = 'Copy legacy unit inspection TDR rows into direct workorder_unit_inspections rows.';

    public function handle(): int
    {
        $write = (bool) $this->option('write');
        $overwrite = (bool) $this->option('overwrite');
        $withTrashed = (bool) $this->option('with-trashed');
        $singleWorkorderId = $this->option('workorder');

        $query = Tdr::query()
            ->unitInspections()
            ->whereNotNull('workorder_id')
            ->whereNotNull('conditions_id')
            ->orderBy('workorder_id')
            ->orderBy('conditions_id')
            ->orderBy('id');

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($singleWorkorderId !== null && $singleWorkorderId !== '') {
            $query->where('workorder_id', (int) $singleWorkorderId);
        }

        $scanned = 0;
        $wouldChange = 0;
        $written = 0;
        $skippedExisting = 0;
        $skippedDuplicateSource = 0;
        $missingWorkorderOrCondition = 0;

        $seenKeys = [];

        $query->chunkById(200, function ($tdrs) use (
            $write,
            $overwrite,
            &$scanned,
            &$wouldChange,
            &$written,
            &$skippedExisting,
            &$skippedDuplicateSource,
            &$missingWorkorderOrCondition,
            &$seenKeys
        ): void {
            foreach ($tdrs as $tdr) {
                $scanned++;

                if (! $tdr->workorder_id || ! $tdr->conditions_id) {
                    $missingWorkorderOrCondition++;
                    continue;
                }

                $key = ((int) $tdr->workorder_id) . ':' . ((int) $tdr->conditions_id);
                if (isset($seenKeys[$key])) {
                    $skippedDuplicateSource++;
                    $this->warn(sprintf(
                        '[WO %d] condition #%d has duplicate source TDR #%d; keeping earlier source TDR #%d.',
                        (int) $tdr->workorder_id,
                        (int) $tdr->conditions_id,
                        (int) $tdr->id,
                        (int) $seenKeys[$key]
                    ));
                    continue;
                }
                $seenKeys[$key] = (int) $tdr->id;

                $target = WorkorderUnitInspection::query()->firstOrNew([
                    'workorder_id' => (int) $tdr->workorder_id,
                    'condition_id' => (int) $tdr->conditions_id,
                ]);

                if ($target->exists && ! $overwrite) {
                    $skippedExisting++;
                    continue;
                }

                $target->fill($this->payloadFromTdr($tdr));

                if ($target->exists && ! $target->isDirty()) {
                    $skippedExisting++;
                    continue;
                }

                $wouldChange++;
                $this->line(sprintf(
                    '[WO %d] condition #%d: source TDR #%d -> workorder_unit_inspection %s',
                    (int) $tdr->workorder_id,
                    (int) $tdr->conditions_id,
                    (int) $tdr->id,
                    $target->exists ? ('#'.(int) $target->id) : 'new'
                ));

                if ($write) {
                    DB::transaction(function () use ($target, &$written): void {
                        $target->save();
                        $written++;
                    });
                }
            }
        });

        $this->table(
            ['scanned', 'would change', 'written', 'skipped existing', 'duplicate source rows', 'missing WO/condition'],
            [[$scanned, $wouldChange, $written, $skippedExisting, $skippedDuplicateSource, $missingWorkorderOrCondition]]
        );

        if (! $write) {
            $this->warn('Dry run only. Run with --write to persist workorder_unit_inspections rows.');
        }

        return self::SUCCESS;
    }

    private function payloadFromTdr(Tdr $tdr): array
    {
        return [
            'source_tdr_id' => $tdr->id,
            'notes' => $tdr->description,
            'qty' => $tdr->qty,
            'serial_number' => $tdr->serial_number,
            'assy_serial_number' => $tdr->assy_serial_number,
            'use_tdr' => (bool) $tdr->use_tdr,
            'use_process_forms' => (bool) $tdr->use_process_forms,
            'source_deleted_at' => $tdr->deleted_at,
        ];
    }
}
