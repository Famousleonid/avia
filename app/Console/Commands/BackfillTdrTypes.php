<?php

namespace App\Console\Commands;

use App\Models\Code;
use App\Models\Necessary;
use App\Models\Tdr;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class BackfillTdrTypes extends Command
{
    use ConfirmableTrait;

    protected $signature = 'tdrs:backfill-types
        {--write : Persist inferred tdr_type values. Default is dry-run}
        {--overwrite : Recalculate rows that already have tdr_type}
        {--chunk=500 : Rows to process per chunk}
        {--limit-unknown=50 : Maximum unknown rows to print}
        {--force : Force the operation to run in production}';

    protected $description = 'Infer and backfill tdrs.tdr_type safely, with dry-run as the default mode.';

    public function handle(): int
    {
        $write = (bool) $this->option('write');
        if ($write && ! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $chunkSize = max(1, (int) $this->option('chunk'));
        $limitUnknown = max(0, (int) $this->option('limit-unknown'));
        $overwrite = (bool) $this->option('overwrite');

        $manufactureCodeId = Code::query()->where('name', 'Manufacture')->value('id');
        $orderNewNecessaryId = Necessary::query()->where('name', 'Order New')->value('id');
        $repairNecessaryId = Necessary::query()->where('name', 'Repair')->value('id');

        $stats = [
            'scanned' => 0,
            'would_update' => 0,
            'updated' => 0,
            'skipped_existing' => 0,
            'unknown' => 0,
        ];
        $byType = array_fill_keys(Tdr::TYPE_OPTIONS, 0);
        $unknownRows = [];

        $query = Tdr::query()
            ->select([
                'id',
                'tdr_type',
                'workorder_id',
                'component_id',
                'order_component_id',
                'codes_id',
                'conditions_id',
                'necessaries_id',
                'description',
                'use_tdr',
                'use_process_forms',
            ])
            ->orderBy('id');

        if (! $overwrite) {
            $query->whereNull('tdr_type');
        }

        $total = (clone $query)->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById($chunkSize, function ($tdrs) use (
            $write,
            $manufactureCodeId,
            $orderNewNecessaryId,
            $repairNecessaryId,
            &$stats,
            &$byType,
            &$unknownRows,
            $limitUnknown,
            $bar
        ): void {
            foreach ($tdrs as $tdr) {
                $stats['scanned']++;

                $type = $tdr->inferType(
                    $manufactureCodeId !== null ? (string) $manufactureCodeId : null,
                    $orderNewNecessaryId !== null ? (string) $orderNewNecessaryId : null,
                    $repairNecessaryId !== null ? (string) $repairNecessaryId : null
                );

                $byType[$type] = ($byType[$type] ?? 0) + 1;
                if ($type === Tdr::TYPE_UNKNOWN) {
                    $stats['unknown']++;
                    if (count($unknownRows) < $limitUnknown) {
                        $unknownRows[] = [
                            'id' => $tdr->id,
                            'wo' => $tdr->workorder_id,
                            'component' => $tdr->component_id,
                            'order component' => $tdr->order_component_id,
                            'code' => $tdr->codes_id,
                            'condition' => $tdr->conditions_id,
                            'necessary' => $tdr->necessaries_id,
                            'description' => $tdr->description,
                            'use_tdr' => (int) $tdr->use_tdr,
                            'use_process_forms' => (int) $tdr->use_process_forms,
                        ];
                    }
                }

                if ($tdr->tdr_type === $type) {
                    $stats['skipped_existing']++;
                    $bar->advance();
                    continue;
                }

                if ($write) {
                    $tdr->tdr_type = $type;
                    $tdr->save();
                    $stats['updated']++;
                } else {
                    $stats['would_update']++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['scanned', 'would update', 'updated', 'skipped existing', 'unknown'],
            [[
                $stats['scanned'],
                $stats['would_update'],
                $stats['updated'],
                $stats['skipped_existing'],
                $stats['unknown'],
            ]]
        );

        $this->table(
            ['type', 'rows'],
            collect($byType)
                ->filter(fn (int $count): bool => $count > 0)
                ->map(fn (int $count, string $type): array => [$type, $count])
                ->values()
                ->all()
        );

        if ($unknownRows !== []) {
            $this->warn('Unknown rows need manual review before queries are switched to tdr_type.');
            $this->table(array_keys($unknownRows[0]), $unknownRows);
        }

        if (! $write) {
            $this->warn('Dry run only. Run with --write to persist inferred tdr_type values.');
        }

        return self::SUCCESS;
    }
}
