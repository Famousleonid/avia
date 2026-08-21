<?php

namespace App\Console\Commands;

use App\Models\Manual;
use App\Models\TrainingCategory;
use App\Models\TrainingMatrixRow;
use Illuminate\Console\Command;

/**
 * Разовый импорт СТРУКТУРЫ матрицы тренингов (группы + строки) из JSON,
 * снятого с Excel «MINIMUM REQUIREMENTS» (только видимые действующие строки).
 * Даты/отметки НЕ импортируются — источник тренингов только таблица trainings.
 *
 * Повторный запуск безопасен: существующие группы/строки (по part_number
 * внутри группы) не дублируются, привязка уже связанных строк не трогается.
 */
class ImportTrainingMatrixStructure extends Command
{
    protected $signature = 'trainings:import-matrix-structure
                            {file=database/data/training_matrix_structure.json}
                            {--dry-run : Показать, что будет сделано, без записи}';

    protected $description = 'Import training matrix structure (groups + rows) and auto-link manuals by part number';

    public function handle(): int
    {
        $path = base_path($this->argument('file'));
        if (!is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $groups = json_decode(file_get_contents($path), true);
        if (!is_array($groups)) {
            $this->error('Invalid JSON.');
            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');

        // Все manuals с training PN, индекс по PN-токенам (см. pnTokens).
        $manuals = Manual::whereNotNull('unit_name_training')
            ->where('unit_name_training', '<>', '')
            ->get(['id', 'title', 'unit_name_training']);
        $byToken = [];
        foreach ($manuals as $m) {
            foreach ($this->pnTokens($m->unit_name_training) as $token) {
                $byToken[$token][$m->id] = $m;
            }
        }

        $linkedManualIds = TrainingMatrixRow::whereNotNull('manual_id')->pluck('manual_id')->all();
        $stats = ['groups' => 0, 'rows' => 0, 'linked' => 0, 'unlinked' => 0, 'ambiguous' => 0, 'skipped' => 0];
        $unlinkedRows = [];
        $ambiguous = [];

        foreach ($groups as $g) {
            $category = TrainingCategory::firstOrNew(['name' => $g['group']]);
            if (!$category->exists) {
                $stats['groups']++;
                $category->sort_order = $g['sort_order'];
                if (!$dry) {
                    $category->save();
                }
            }

            foreach ($g['rows'] as $r) {
                $exists = $category->exists && TrainingMatrixRow::where('training_category_id', $category->id)
                    ->where('part_number', $r['part_number'])
                    ->exists();
                if ($exists) {
                    $stats['skipped']++;
                    continue;
                }

                $candidates = collect();
                foreach ($this->pnTokens($r['part_number']) as $token) {
                    foreach ($byToken[$token] ?? [] as $m) {
                        $candidates->put($m->id, $m);
                    }
                }
                $candidates = $candidates
                    ->reject(fn ($m) => in_array($m->id, $linkedManualIds, true))
                    ->values();

                $manualId = null;
                if ($candidates->count() === 1) {
                    $manualId = $candidates->first()->id;
                    $linkedManualIds[] = $manualId;
                    $stats['linked']++;
                } elseif ($candidates->count() > 1) {
                    $stats['ambiguous']++;
                    $ambiguous[] = "{$g['group']} | {$r['part_number']} -> " .
                        $candidates->map(fn ($m) => "#{$m->id} {$m->title}")->implode('; ');
                } else {
                    $stats['unlinked']++;
                    $unlinkedRows[] = "{$g['group']} | {$r['part_number']}" .
                        ($r['description'] ? " ({$r['description']})" : '');
                }

                $stats['rows']++;
                if (!$dry) {
                    TrainingMatrixRow::create([
                        'training_category_id' => $category->id,
                        'description' => $r['description'] ?? null,
                        'part_number' => $r['part_number'],
                        'sort_order' => $r['sort_order'],
                        'manual_id' => $manualId,
                    ]);
                }
            }
        }

        $this->info(($dry ? '[DRY RUN] ' : '') .
            "Groups created: {$stats['groups']}, rows created: {$stats['rows']}, " .
            "linked: {$stats['linked']}, without CMM: {$stats['unlinked']}, " .
            "ambiguous: {$stats['ambiguous']}, skipped existing: {$stats['skipped']}");

        if ($unlinkedRows) {
            $this->line('');
            $this->warn('Rows without CMM in avia (visible as "CMM not registered"):');
            foreach ($unlinkedRows as $row) {
                $this->line('  ' . $row);
            }
        }
        if ($ambiguous) {
            $this->line('');
            $this->warn('Ambiguous matches (link manually):');
            foreach ($ambiguous as $row) {
                $this->line('  ' . $row);
            }
        }

        // Мануалы с training PN, не попавшие ни в одну строку.
        $orphan = $manuals->reject(fn ($m) => in_array($m->id, $linkedManualIds, true));
        if ($orphan->isNotEmpty()) {
            $this->line('');
            $this->warn('Manuals with training PN not matched to any matrix row:');
            foreach ($orphan as $m) {
                $this->line("  #{$m->id} {$m->title} [{$m->unit_name_training}]");
            }
        }

        return self::SUCCESS;
    }

    private function pnTokens(?string $raw): array
    {
        return TrainingMatrixRow::pnTokens($raw);
    }
}

