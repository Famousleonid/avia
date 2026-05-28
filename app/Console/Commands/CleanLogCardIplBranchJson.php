<?php

namespace App\Console\Commands;

use App\Models\Component;
use App\Models\LogCard;
use App\Services\ManualIplBranchRuleResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanLogCardIplBranchJson extends Command
{
    protected $signature = 'log-cards:clean-ipl-branch-json
        {--commit : Save changes. Without this flag the command only reports what would be removed.}
        {--workorder= : Limit cleanup to one workorder id.}
        {--manual= : Limit cleanup to one manual id.}
        {--limit= : Stop after this many changed log cards.}
        {--details : Print removed rows.}';

    protected $description = 'Remove log card JSON rows whose component IPL branch is not allowed for the workorder unit.';

    public function handle(ManualIplBranchRuleResolver $resolver): int
    {
        $commit = (bool) $this->option('commit');
        $workorderId = $this->positiveIntOption('workorder');
        $manualId = $this->positiveIntOption('manual');
        $limit = $this->positiveIntOption('limit');
        $details = (bool) $this->option('details');

        $query = LogCard::query()
            ->with(['workorder.unit'])
            ->whereHas('workorder.unit', function ($query) use ($manualId): void {
                if ($manualId !== null) {
                    $query->where('manual_id', $manualId);
                }
            })
            ->orderBy('id');

        if ($workorderId !== null) {
            $query->where('workorder_id', $workorderId);
        }

        $this->info(($commit ? 'COMMIT' : 'DRY-RUN').' log card IPL branch JSON cleanup');

        $checkedCards = 0;
        $changedCards = 0;
        $removedRows = 0;
        $componentCache = [];

        $query->chunkById(100, function ($logCards) use (
            $resolver,
            $commit,
            $limit,
            $details,
            &$checkedCards,
            &$changedCards,
            &$removedRows,
            &$componentCache
        ): bool {
            foreach ($logCards as $logCard) {
                $checkedCards++;
                $workorder = $logCard->workorder;
                $unit = $workorder?->unit;

                if (! $workorder || ! $unit) {
                    continue;
                }

                $before = [
                    'component_data' => $this->decodeRows($logCard->getRawOriginal('component_data')),
                    'component_data_out' => $this->decodeRows($logCard->getRawOriginal('component_data_out')),
                ];
                $after = $before;
                $removed = [];

                foreach (array_keys($after) as $field) {
                    [$after[$field], $fieldRemoved] = $this->filterRows(
                        $after[$field],
                        $field,
                        $unit,
                        $resolver,
                        $componentCache
                    );
                    $removed = array_merge($removed, $fieldRemoved);
                }

                if ($removed === []) {
                    continue;
                }

                $changedCards++;
                $removedRows += count($removed);
                $this->line(sprintf(
                    'LogCard #%d WO #%s: remove %d row(s)',
                    $logCard->id,
                    $workorder->id,
                    count($removed)
                ));

                if ($details) {
                    foreach ($removed as $row) {
                        $this->line(sprintf(
                            '  - %s[%d] component #%d %s %s',
                            $row['field'],
                            $row['index'],
                            $row['component_id'],
                            $row['ipl_num'],
                            $row['part_number']
                        ));
                    }
                }

                if ($commit) {
                    DB::transaction(function () use ($logCard, $before, $after, $removed): void {
                        $logCard->component_data = json_encode(array_values($after['component_data']), JSON_UNESCAPED_UNICODE);
                        $logCard->component_data_out = array_values($after['component_data_out']);
                        $logCard->save();

                        $changes = LogCard::buildActivityChanges([
                            'component_data' => [$before['component_data'], $after['component_data']],
                            'component_data_out' => [$before['component_data_out'], $after['component_data_out']],
                        ]);
                        $logCard->logActivityEvent('updated', $changes['old'], $changes['attributes'], [
                            'source' => 'log_cards_clean_ipl_branch_json',
                            'removed_rows' => $removed,
                        ]);
                    });
                }

                if ($limit !== null && $changedCards >= $limit) {
                    return false;
                }
            }

            return true;
        });

        $this->info(sprintf(
            'Checked %d log card(s), %s %d row(s) in %d log card(s).',
            $checkedCards,
            $commit ? 'removed' : 'would remove',
            $removedRows,
            $changedCards
        ));

        if (! $commit) {
            $this->warn('Dry-run only. Re-run with --commit to save changes.');
        }

        return self::SUCCESS;
    }

    private function positiveIntOption(string $name): ?int
    {
        $value = $this->option($name);
        if ($value === null || $value === '') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeRows(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, 'is_array'));
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, Component|null> $componentCache
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function filterRows(
        array $rows,
        string $field,
        $unit,
        ManualIplBranchRuleResolver $resolver,
        array &$componentCache
    ): array {
        $kept = [];
        $removed = [];
        $manualId = (int) ($unit->manual_id ?? 0);

        foreach ($rows as $index => $row) {
            $componentId = (int) ($row['component_id'] ?? 0);
            if ($componentId <= 0) {
                $kept[] = $row;
                continue;
            }

            if (! array_key_exists($componentId, $componentCache)) {
                $componentCache[$componentId] = Component::query()
                    ->select('id', 'ipl_num', 'part_number')
                    ->find($componentId);
            }

            $component = $componentCache[$componentId];
            if (! $component) {
                $kept[] = $row;
                continue;
            }

            if ($resolver->allowsComponentForUnit($unit, (string) ($component->ipl_num ?? ''), $manualId)) {
                $kept[] = $row;
                continue;
            }

            $removed[] = [
                'field' => $field,
                'index' => $index,
                'component_id' => $componentId,
                'ipl_num' => (string) ($component->ipl_num ?? ''),
                'part_number' => (string) ($component->part_number ?? ''),
            ];
        }

        return [$kept, $removed];
    }
}
