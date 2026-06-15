<?php

namespace App\Console\Commands;

use App\Models\Manual;
use App\Models\ManualFit;
use App\Models\ManualParameter;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * Backfill manual_fits from the legacy "shared point" pairing.
 *
 * Historically an OD↔ID pair was inferred at runtime: an OD parameter and
 * another component's parameter that shared a measurement point. This command
 * makes those pairs explicit as ManualFit rows so the F&C logic can stop
 * inferring. Only legacy shared-point pairs are migrated; genuinely new pairs
 * whose members sit on different points are added later via authoring.
 *
 * Heuristic (same as requiredBushings): OD member = parameter whose description
 * matches \bOD\b and has an orig limit; mate (ID member) = a parameter on a
 * DIFFERENT inspection component, sharing at least one point, with an orig
 * limit. A fit is created per (OD, mate). Clearances are left null so the model
 * derives them; an engineer fills the manual values later (that becomes the
 * "from manual" flag). Idempotent: an existing (od_param_id, id_param_id) is skipped.
 */
class BackfillManualFits extends Command
{
    use ConfirmableTrait;

    protected $signature = 'fits:backfill
        {--manual= : Limit to a single manual id}
        {--write : Persist created fits. Default is dry-run}
        {--sample=30 : Maximum pairs to print}
        {--force : Force the operation to run in production}';

    protected $description = 'Backfill manual_fits from legacy shared-point OD↔ID pairs (dry-run by default).';

    public function handle(): int
    {
        $write = (bool) $this->option('write');
        if ($write && ! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $sampleLimit = max(0, (int) $this->option('sample'));

        $manualQuery = Manual::query()->orderBy('id');
        if ($this->option('manual') !== null) {
            $manualQuery->where('id', (int) $this->option('manual'));
        }

        $stats = ['manuals' => 0, 'od_params' => 0, 'created' => 0, 'would_create' => 0, 'skipped_existing' => 0];
        $sample = [];

        foreach ($manualQuery->cursor() as $manual) {
            $stats['manuals']++;

            $params = ManualParameter::where('manual_id', $manual->id)
                ->with('points:id,code')
                ->get();

            $odParams = $params->filter(fn ($p) =>
                ($p->orig_dim_min !== null || $p->orig_dim_max !== null)
                && preg_match('/\bOD\b/i', (string) $p->description) === 1);

            foreach ($odParams as $od) {
                $stats['od_params']++;
                $odPointIds = $od->points->pluck('id');
                if ($odPointIds->isEmpty()) {
                    continue;
                }

                $mates = $params->filter(fn ($p) =>
                    $p->id !== $od->id
                    && $p->inspection_component_id !== $od->inspection_component_id
                    && ($p->orig_dim_min !== null || $p->orig_dim_max !== null)
                    && $p->points->pluck('id')->intersect($odPointIds)->isNotEmpty());

                foreach ($mates as $mate) {
                    $exists = ManualFit::where('od_param_id', $od->id)
                        ->where('id_param_id', $mate->id)
                        ->exists();

                    if ($exists) {
                        $stats['skipped_existing']++;
                        continue;
                    }

                    if (count($sample) < $sampleLimit) {
                        $sample[] = [
                            'manual'   => $manual->id,
                            'point(s)' => $od->points->pluck('code')->filter()->implode(', '),
                            'OD param' => $od->id . ' · ' . $od->description,
                            'ID param' => $mate->id . ' · ' . $mate->description,
                        ];
                    }

                    if ($write) {
                        ManualFit::create([
                            'manual_id'   => $manual->id,
                            'od_param_id' => $od->id,
                            'id_param_id' => $mate->id,
                            'sort_order'  => $stats['created'],
                        ]);
                        $stats['created']++;
                    } else {
                        $stats['would_create']++;
                    }
                }
            }
        }

        $this->newLine();
        $this->table(
            ['manuals', 'OD params', 'created', 'would create', 'skipped existing'],
            [[$stats['manuals'], $stats['od_params'], $stats['created'], $stats['would_create'], $stats['skipped_existing']]]
        );

        if ($sample !== []) {
            $this->table(array_keys($sample[0]), $sample);
        }

        if (! $write) {
            $this->warn('Dry run only. Run with --write to persist the fits.');
        }

        return self::SUCCESS;
    }
}
