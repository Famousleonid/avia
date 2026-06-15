<?php

namespace App\Console\Commands;

use App\Models\Manual;
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
        {--force : Force the operation to run in production}';

    protected $description = 'Backfill manual_fits from Fits & Clearances points (delegates to ManualFitController::detectPairs).';

    public function handle(\App\Http\Controllers\Admin\ManualFitController $fits): int
    {
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $manualQuery = Manual::query()->orderBy('id');
        if ($this->option('manual') !== null) {
            $manualQuery->where('id', (int) $this->option('manual'));
        }

        $stats = ['manuals' => 0, 'created' => 0, 'skipped_existing' => 0];

        foreach ($manualQuery->cursor() as $manual) {
            $stats['manuals']++;
            [$created, $skipped] = $fits->detectPairs($manual);
            $stats['created'] += $created;
            $stats['skipped_existing'] += $skipped;
        }

        $this->newLine();
        $this->table(
            ['manuals', 'created', 'skipped existing'],
            [[$stats['manuals'], $stats['created'], $stats['skipped_existing']]]
        );

        return self::SUCCESS;
    }
}
