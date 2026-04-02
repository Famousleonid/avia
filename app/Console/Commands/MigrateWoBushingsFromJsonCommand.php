<?php

namespace App\Console\Commands;

use App\Models\WoBushing;
use App\Services\WoBushingRelationalSync;
use Illuminate\Console\Command;

class MigrateWoBushingsFromJsonCommand extends Command
{
    protected $signature = 'wo-bushings:migrate-json
                            {--force : Перезаписать уже перенесённые (удалить строки и импортировать снова)}
                            {--dry-run : Только показать, что будет сделано}';

    protected $description = 'Перенос bush_data (JSON) в wo_bushing_lines и wo_bushing_processes';

    public function handle(WoBushingRelationalSync $sync): int
    {
        $force = (bool) $this->option('force');
        $dry = (bool) $this->option('dry-run');

        $query = WoBushing::query()->orderBy('id');

        $migrated = 0;
        $skipped = 0;

        foreach ($query->cursor() as $woBushing) {
            $hasJson = $woBushing->bush_data !== null
                && $woBushing->bush_data !== []
                && $woBushing->bush_data !== '';

            if (! $hasJson) {
                $skipped++;

                continue;
            }

            $hasLines = $woBushing->lines()->exists();

            if ($hasLines && ! $force) {
                $this->line("Skip #{$woBushing->id} (WO {$woBushing->workorder_id}): уже есть строки, используйте --force");
                $skipped++;

                continue;
            }

            if ($dry) {
                $this->line("Would migrate #{$woBushing->id} (WO {$woBushing->workorder_id})".($hasLines && $force ? ' [overwrite]' : ''));

                continue;
            }

            if ($sync->migrateLegacyJsonToRelations($woBushing, $force)) {
                $this->info("OK #{$woBushing->id} (WO {$woBushing->workorder_id})");
                $migrated++;
            } else {
                $this->warn("Skip #{$woBushing->id}: пустой bush_data");
                $skipped++;
            }
        }

        if (! $dry) {
            $this->newLine();
            $this->info("Готово: перенесено {$migrated}, пропущено {$skipped}.");
        } else {
            $this->info('Dry-run завершён.');
        }

        return self::SUCCESS;
    }
}
