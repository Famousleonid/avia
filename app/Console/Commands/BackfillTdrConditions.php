<?php

namespace App\Console\Commands;

use App\Models\Code;
use App\Models\Condition;
use App\Models\Tdr;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * Backfill tdrs.conditions_id for component rows to match the server-authoritative
 * rule now used at creation (TdrController::store):
 *   - Missing code      → "PARTS MISSING UPON ARRIVAL …" (resolved by NAME),
 *   - any other code    → condition whose name equals the code name, else null.
 *
 * Fixes legacy rows whose condition was set from the old JS mapping (magic ids /
 * default 39 = SERVICE BULLETIN CHANGE), e.g. a "Customer Request" part that was
 * stored with conditions_id = 39. Manufacture rows are left untouched (their
 * two-record condition layout is set explicitly server-side). Null-component
 * rows (unit inspections / STD-list carriers) are out of scope — there the
 * condition is the real datum.
 *
 * Idempotent: a row already holding the target condition is skipped.
 */
class BackfillTdrConditions extends Command
{
    use ConfirmableTrait;

    protected $signature = 'tdrs:backfill-conditions
        {--workorder= : Limit to a single workorder id}
        {--dry-run : Report what would change without writing}
        {--force : Force the operation to run in production}';

    protected $description = 'Re-derive tdrs.conditions_id for component rows by code name (Missing→PARTS MISSING), matching TdrController::store.';

    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $missingCode = Code::missing();
        $manufactureCode = Code::where('name', 'Manufacture')->first();
        $partsMissingId = optional(Condition::partsMissing())->id;

        // code id → resolved condition id (null = no same-named condition).
        $conditionByCode = [];
        $resolveConditionId = function (?int $codeId) use (&$conditionByCode, $missingCode, $partsMissingId): ?int {
            if ($codeId === null) {
                return null;
            }
            if ($missingCode && $codeId === (int) $missingCode->id) {
                return $partsMissingId;
            }
            if (! array_key_exists($codeId, $conditionByCode)) {
                $code = Code::find($codeId);
                $conditionByCode[$codeId] = $code
                    ? optional(
                        Condition::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim((string) $code->name))])->first()
                    )->id
                    : null;
            }

            return $conditionByCode[$codeId];
        };

        $query = Tdr::query()->whereNotNull('component_id');
        if ($this->option('workorder') !== null) {
            $query->where('workorder_id', (int) $this->option('workorder'));
        }
        if ($manufactureCode) {
            $query->where(function ($q) use ($manufactureCode) {
                $q->whereNull('codes_id')->orWhere('codes_id', '!=', $manufactureCode->id);
            });
        }

        $scanned = 0;
        $changed = 0;

        foreach ($query->cursor() as $tdr) {
            $scanned++;
            $target = $resolveConditionId($tdr->codes_id !== null ? (int) $tdr->codes_id : null);
            $current = $tdr->conditions_id === null ? null : (int) $tdr->conditions_id;

            if ($current === $target) {
                continue;
            }

            $changed++;
            if (! $dryRun) {
                $tdr->conditions_id = $target;
                $tdr->save();
            }
        }

        $this->newLine();
        $this->table(
            ['scanned', $dryRun ? 'would change' : 'changed'],
            [[$scanned, $changed]]
        );

        return self::SUCCESS;
    }
}
