<?php

namespace App\Console\Commands;

use App\Models\Manual;
use App\Models\StdProcess;
use Illuminate\Console\Command;

class ImportStdProcessesFromMedia extends Command
{
    protected $signature = 'std-processes:import-from-media {--manual= : Manual ID (optional, default: all manuals)}';

    protected $description = 'Заполнить std_processes из прикреплённых csv_files (только если таблица для типа пуста)';

    public function handle(): int
    {
        $manualId = $this->option('manual');
        $query = Manual::query();
        if ($manualId !== null && $manualId !== '') {
            $query->where('id', (int) $manualId);
        }

        $count = 0;
        foreach ($query->cursor() as $manual /** @var Manual $manual */) {
            foreach (StdProcess::validStdValues() as $std) {
                if (StdProcess::query()->where('manual_id', $manual->id)->where('std', $std)->exists()) {
                    continue;
                }
                if (StdProcess::syncFromMediaIfEmpty($manual, $std)) {
                    $n = StdProcess::query()->where('manual_id', $manual->id)->where('std', $std)->count();
                    if ($n > 0) {
                        $this->line("Manual {$manual->id} ({$manual->number}): {$std} — импортировано {$n} строк");
                        $count += $n;
                    }
                }
            }
        }

        $this->info("Готово. Импортировано строк (суммарно): {$count}");

        return self::SUCCESS;
    }
}
