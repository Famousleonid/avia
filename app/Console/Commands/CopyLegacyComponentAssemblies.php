<?php

namespace App\Console\Commands;

use App\Models\Component;
use App\Models\ComponentAssembly;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class CopyLegacyComponentAssemblies extends Command
{
    use ConfirmableTrait;

    protected $signature = 'components:copy-legacy-assy
        {--dry-run : Show what would be copied without writing}
        {--chunk=500 : Components to process per chunk}
        {--with-trashed : Include soft-deleted components}
        {--restore-trashed-assemblies : Restore a matching soft-deleted assembly instead of skipping it}
        {--force : Force the operation to run in production}';

    protected $description = 'Copy legacy components assy fields into component_assemblies without deleting legacy fields.';

    public function handle(): int
    {
        if (! $this->option('dry-run') && ! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $stats = [
            'eligible' => 0,
            'created' => 0,
            'updated' => 0,
            'restored' => 0,
            'skipped_existing' => 0,
            'skipped_trashed_existing' => 0,
        ];

        $query = Component::query()
            ->select(['id', 'assy_part_number', 'assy_ipl_num', 'units_assy'])
            ->where(function ($query) {
                $query->whereNotNull('assy_part_number')
                    ->where('assy_part_number', '<>', '')
                    ->orWhere(function ($query) {
                        $query->whereNotNull('assy_ipl_num')
                            ->where('assy_ipl_num', '<>', '');
                    });
            })
            ->orderBy('id');

        if ($this->option('with-trashed')) {
            $query->withTrashed();
        }

        $bar = $this->output->createProgressBar((clone $query)->count());
        $bar->start();

        $query->chunkById($chunkSize, function ($components) use (&$stats, $dryRun, $bar) {
            foreach ($components as $component) {
                $stats['eligible']++;

                $assyPartNumber = trim((string) $component->assy_part_number);
                $assyIplNum = trim((string) $component->assy_ipl_num);
                $unitsAssy = trim((string) $component->units_assy);

                $existing = ComponentAssembly::withTrashed()
                    ->where('component_id', $component->id)
                    ->where('assy_part_number', $assyPartNumber)
                    ->where(function ($query) use ($assyIplNum) {
                        if ($assyIplNum === '') {
                            $query->whereNull('assy_ipl_num')->orWhere('assy_ipl_num', '');
                        } else {
                            $query->where('assy_ipl_num', $assyIplNum);
                        }
                    })
                    ->first();

                if ($existing) {
                    if ($existing->trashed()) {
                        if ($this->option('restore-trashed-assemblies') && ! $dryRun) {
                            $existing->restore();
                            $stats['restored']++;
                        } else {
                            $stats['skipped_trashed_existing']++;
                        }
                    } elseif ($unitsAssy !== '' && $existing->units_assy !== $unitsAssy) {
                        if (! $dryRun) {
                            $existing->update(['units_assy' => $unitsAssy]);
                        }
                        $stats['updated']++;
                    } else {
                        $stats['skipped_existing']++;
                    }

                    $bar->advance();
                    continue;
                }

                if (! $dryRun) {
                    ComponentAssembly::create([
                        'component_id' => $component->id,
                        'assy_part_number' => $assyPartNumber,
                        'assy_ipl_num' => $assyIplNum !== '' ? $assyIplNum : null,
                        'units_assy' => $unitsAssy !== '' ? $unitsAssy : null,
                        'sort_order' => 0,
                        'notes' => null,
                    ]);
                }

                $stats['created']++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['eligible', 'created', 'updated', 'restored', 'skipped existing', 'skipped trashed existing'],
            [[
                $stats['eligible'],
                $stats['created'],
                $stats['updated'],
                $stats['restored'],
                $stats['skipped_existing'],
                $stats['skipped_trashed_existing'],
            ]]
        );

        if ($dryRun) {
            $this->warn('Dry run only. Run without --dry-run to write rows.');
        }

        return self::SUCCESS;
    }
}
