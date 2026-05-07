<?php

namespace App\Console\Commands;

use App\Models\Component;
use App\Models\LogCard;
use App\Models\Manual;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeDuplicateComponents extends Command
{
    protected $signature = 'components:merge-duplicates
        {--key=manual-ipl : Duplicate key: manual-ipl or manual-part-ipl}
        {--manual= : Limit to one manual id}
        {--limit=200 : Max duplicate groups to show}
        {--group= : 1-based group number from the displayed list}
        {--canonical= : Component id to keep}
        {--yes : Apply without confirmation}
        {--dry-run : Show the merge plan without changing data}';

    protected $description = 'Merge duplicate components by moving references to one canonical component.';

    public function handle(): int
    {
        $key = (string) $this->option('key');
        if (! in_array($key, ['manual-ipl', 'manual-part-ipl'], true)) {
            $this->error('Invalid --key. Use manual-ipl or manual-part-ipl.');

            return self::FAILURE;
        }

        $groups = $this->duplicateGroups($key)->take(max(1, (int) $this->option('limit')))->values();
        if ($groups->isEmpty()) {
            $this->info('No duplicate component groups found.');

            return self::SUCCESS;
        }

        $reports = $groups->map(fn ($group, $index) => $this->groupReport($group, $key, $index + 1));

        if ($this->option('group')) {
            $this->printGroups($reports);
            $groupNumber = $this->selectGroupNumber($reports->count());

            return $this->processReport($reports[$groupNumber - 1]);
        }

        $this->info('Duplicate groups found: '.$reports->count());
        $this->line('Going group by group. Enter canonical id, "s" to skip, or "q" to quit.');

        foreach ($reports as $report) {
            $status = $this->processReport($report, allowSkip: true);
            if ($status === self::FAILURE) {
                return self::FAILURE;
            }

            if ($status === 2) {
                $this->warn('Stopped by user.');

                return self::SUCCESS;
            }
        }

        return self::SUCCESS;
    }

    private function processReport(array $report, bool $allowSkip = false): int
    {
        $componentIds = collect($report['components'])->pluck('id')->all();

        $this->newLine();
        $this->warn(sprintf(
            'Group #%d | Manual %s %s | PN: %s | IPL: %s | Qty: %d',
            $report['number'],
            $report['manual_id'],
            $report['manual'],
            $report['part_number'] ?? '*',
            $report['ipl_num'] ?? '*',
            $report['qty'],
        ));
        $this->table(['id', 'name', 'pn', 'ipl', 'log', 'refs', 'ref detail'], collect($report['components'])->map(fn ($component) => [
            'id' => $component['id'],
            'name' => $component['name'],
            'pn' => $component['part_number'],
            'ipl' => $component['ipl_num'],
            'log' => $component['log_card'] ? 'yes' : 'no',
            'refs' => $component['total_refs'],
            'ref detail' => $this->formatRefs($component['refs']),
        ])->all());

        $canonicalId = $this->selectCanonicalId($componentIds, $report['suggested_canonical_id'], $allowSkip);
        if ($canonicalId === 'skip') {
            $this->warn('Skipped group #'.$report['number'].'.');

            return self::SUCCESS;
        }

        if ($canonicalId === 'quit') {
            return 2;
        }

        $duplicateIds = array_values(array_diff($componentIds, [$canonicalId]));

        $this->newLine();
        $this->warn('Merge plan');
        $this->line('Keep canonical component: '.$canonicalId);
        $this->line('Merge and soft-delete: '.implode(', ', $duplicateIds));

        if ($this->option('dry-run')) {
            $this->info('Dry run only. No data changed.');

            return self::SUCCESS;
        }

        if (! $this->option('yes') && ! $this->confirm('Move all references to '.$canonicalId.' and soft-delete duplicate ids?', false)) {
            $this->warn('Cancelled group #'.$report['number'].'. No data changed.');

            return self::SUCCESS;
        }

        $result = DB::transaction(fn () => $this->merge($canonicalId, $duplicateIds));

        $this->info('Merge completed for group #'.$report['number'].'.');
        $this->table(['item', 'changed'], collect($result)->map(fn ($count, $item) => [
            'item' => $item,
            'changed' => $count,
        ])->all());

        return self::SUCCESS;
    }

    private function duplicateGroups(string $key): Collection
    {
        $query = Component::query()
            ->selectRaw($this->groupSelect($key).', COUNT(*) as qty')
            ->whereNull('deleted_at')
            ->whereNotNull('manual_id')
            ->whereNotNull('ipl_num')
            ->where('ipl_num', '<>', '');

        if ($key === 'manual-part-ipl') {
            $query->whereNotNull('part_number')->where('part_number', '<>', '');
        }

        if ($this->option('manual')) {
            $query->where('manual_id', (int) $this->option('manual'));
        }

        return $query
            ->groupBy(...$this->groupColumns($key))
            ->having('qty', '>', 1)
            ->orderByDesc('qty')
            ->orderBy('manual_id')
            ->get();
    }

    private function groupReport(object $group, string $key, int $number): array
    {
        $manual = Manual::find($group->manual_id);
        $components = $this->componentsForGroup($group, $key)
            ->map(function (Component $component) {
                $refs = $this->componentRefs($component->id);

                return [
                    'id' => (int) $component->id,
                    'name' => (string) $component->name,
                    'part_number' => (string) $component->part_number,
                    'ipl_num' => (string) $component->ipl_num,
                    'log_card' => (bool) $component->log_card,
                    'is_bush' => (bool) $component->is_bush,
                    'bush_ipl_num' => (string) $component->bush_ipl_num,
                    'refs' => $refs,
                    'total_refs' => array_sum($refs),
                ];
            })
            ->values();

        $suggested = $components->sortByDesc('total_refs')->first();

        return [
            'number' => $number,
            'manual_id' => (int) $group->manual_id,
            'manual' => trim(($manual?->number ?? '-').' ('.($manual?->lib ?? '-').')'),
            'part_number' => $group->part_number ?? null,
            'ipl_num' => $group->ipl_num ?? null,
            'qty' => (int) $group->qty,
            'suggested_canonical_id' => $suggested && $suggested['total_refs'] > 0 ? $suggested['id'] : null,
            'components' => $components->all(),
        ];
    }

    private function printGroups(Collection $reports): void
    {
        $this->table(
            ['#', 'manual', 'PN', 'IPL', 'qty', 'ids', 'suggested'],
            $reports->map(fn ($report) => [
                '#' => $report['number'],
                'manual' => $report['manual_id'].' '.$report['manual'],
                'PN' => $report['part_number'] ?? '*',
                'IPL' => $report['ipl_num'] ?? '*',
                'qty' => $report['qty'],
                'ids' => collect($report['components'])->pluck('id')->implode(', '),
                'suggested' => $report['suggested_canonical_id'] ?? '-',
            ])->all()
        );
    }

    private function selectGroupNumber(int $max): int
    {
        $group = $this->option('group') ?: $this->ask('Select duplicate group number');
        $group = (int) $group;

        if ($group < 1 || $group > $max) {
            throw new \InvalidArgumentException('Invalid group number.');
        }

        return $group;
    }

    private function selectCanonicalId(array $componentIds, ?int $suggested, bool $allowSkip = false): int|string
    {
        if ($this->option('canonical')) {
            $canonical = $this->option('canonical');
        } else {
            $prompt = 'Canonical component id to keep'.($suggested ? ' ['.$suggested.']' : '');
            if ($allowSkip) {
                $prompt .= ' / s=skip / q=quit';
            }

            $canonical = $this->ask($prompt, $suggested);
        }

        if ($allowSkip && strtolower((string) $canonical) === 's') {
            return 'skip';
        }

        if ($allowSkip && strtolower((string) $canonical) === 'q') {
            return 'quit';
        }

        $canonical = (int) $canonical;

        if (! in_array($canonical, $componentIds, true)) {
            throw new \InvalidArgumentException('Canonical id must be one of: '.implode(', ', $componentIds));
        }

        return $canonical;
    }

    private function formatRefs(array $refs): string
    {
        if ($refs === []) {
            return '-';
        }

        return collect($refs)
            ->map(fn ($count, $key) => $key.':'.$count)
            ->implode(', ');
    }

    private function merge(int $canonicalId, array $duplicateIds): array
    {
        $canonical = Component::query()->findOrFail($canonicalId);
        $duplicates = Component::query()->whereIn('id', $duplicateIds)->get();
        $counts = [
            'direct_reference_rows' => 0,
            'log_cards' => 0,
            'media_rows' => 0,
            'activity_log_rows' => 0,
            'canonical_fields' => 0,
            'soft_deleted_components' => 0,
        ];

        foreach ($duplicates as $duplicate) {
            $counts['direct_reference_rows'] += $this->moveDirectReferences($duplicate->id, $canonicalId);
            $counts['log_cards'] += $this->moveLogCardReferences($duplicate->id, $canonicalId);
            $counts['media_rows'] += $this->moveMorphReferences('media', 'model_type', 'model_id', $duplicate->id, $canonicalId);
            $counts['activity_log_rows'] += $this->moveMorphReferences('activity_log', 'subject_type', 'subject_id', $duplicate->id, $canonicalId);
            $counts['canonical_fields'] += $this->mergeCanonicalFields($canonical, $duplicate);
        }

        foreach ($duplicates as $duplicate) {
            $duplicate->delete();
            $counts['soft_deleted_components']++;
        }

        return $counts;
    }

    private function moveDirectReferences(int $fromId, int $toId): int
    {
        $changed = 0;

        foreach ($this->componentReferenceColumns() as $table => $columns) {
            foreach ($columns as $column) {
                $changed += DB::table($table)->where($column, $fromId)->update([$column => $toId]);
            }
        }

        return $changed;
    }

    private function moveLogCardReferences(int $fromId, int $toId): int
    {
        if (! Schema::hasTable('log_cards')) {
            return 0;
        }

        $changed = 0;
        LogCard::query()
            ->whereNotNull('component_data')
            ->chunkById(100, function (Collection $logCards) use ($fromId, $toId, &$changed) {
                foreach ($logCards as $logCard) {
                    $data = json_decode((string) $logCard->component_data, true);
                    if (! is_array($data)) {
                        continue;
                    }

                    $rewritten = $this->replaceComponentIdInArray($data, $fromId, $toId);
                    if ($rewritten === $data) {
                        continue;
                    }

                    $logCard->component_data = json_encode($rewritten, JSON_UNESCAPED_SLASHES);
                    $logCard->save();
                    $changed++;
                }
            });

        return $changed;
    }

    private function replaceComponentIdInArray(array $data, int $fromId, int $toId): array
    {
        foreach ($data as $key => $value) {
            if ($key === 'component_id' && (string) $value === (string) $fromId) {
                $data[$key] = is_string($value) ? (string) $toId : $toId;
                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->replaceComponentIdInArray($value, $fromId, $toId);
            }
        }

        return $data;
    }

    private function moveMorphReferences(string $table, string $typeColumn, string $idColumn, int $fromId, int $toId): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $componentTypes = array_values(array_unique([
            Component::class,
            (new Component())->getMorphClass(),
        ]));

        return DB::table($table)
            ->whereIn($typeColumn, $componentTypes)
            ->where($idColumn, $fromId)
            ->update([$idColumn => $toId]);
    }

    private function mergeCanonicalFields(Component $canonical, Component $duplicate): int
    {
        $updates = [];
        foreach (['log_card', 'is_bush', 'kit', 'ndt_list', 'cad_list', 'stress_relief_list', 'paint_list'] as $field) {
            if (! $canonical->{$field} && $duplicate->{$field}) {
                $updates[$field] = true;
            }
        }

        foreach (['bush_ipl_num', 'assy_part_number', 'assy_ipl_num', 'eff_code', 'units_assy'] as $field) {
            if (blank($canonical->{$field}) && filled($duplicate->{$field})) {
                $updates[$field] = $duplicate->{$field};
            }
        }

        if ($updates === []) {
            return 0;
        }

        $canonical->forceFill($updates)->save();
        $canonical->refresh();

        return count($updates);
    }

    private function componentsForGroup(object $group, string $key): Collection
    {
        $query = Component::query()
            ->where('manual_id', $group->manual_id)
            ->where('ipl_num', $group->ipl_num);

        if ($key === 'manual-part-ipl') {
            $query->where('part_number', $group->part_number);
        }

        return $query->orderBy('id')->get();
    }

    private function componentRefs(int $componentId): array
    {
        $refs = [];

        foreach ($this->componentReferenceColumns() as $table => $columns) {
            foreach ($columns as $column) {
                $count = DB::table($table)->where($column, $componentId)->count();
                if ($count > 0) {
                    $refs[$table.'.'.$column] = $count;
                }
            }
        }

        $logCardCount = $this->logCardReferenceCount($componentId);
        if ($logCardCount > 0) {
            $refs['log_cards.component_data'] = $logCardCount;
        }

        foreach ($this->morphRefs($componentId) as $key => $count) {
            if ($count > 0) {
                $refs[$key] = $count;
            }
        }

        return $refs;
    }

    private function componentReferenceColumns(): array
    {
        $database = DB::getDatabaseName();
        $rows = DB::select(
            'select TABLE_NAME as table_name, COLUMN_NAME as column_name from information_schema.COLUMNS where TABLE_SCHEMA = ? and COLUMN_NAME in (?, ?)',
            [$database, 'component_id', 'order_component_id']
        );

        $refs = [];
        foreach ($rows as $row) {
            $table = (string) $row->table_name;
            if ($table === 'components') {
                continue;
            }

            $refs[$table][] = (string) $row->column_name;
        }

        ksort($refs);

        return $refs;
    }

    private function logCardReferenceCount(int $componentId): int
    {
        if (! Schema::hasTable('log_cards')) {
            return 0;
        }

        return LogCard::query()
            ->where(function ($query) use ($componentId) {
                $query
                    ->where('component_data', 'like', '%"component_id":"'.$componentId.'"%')
                    ->orWhere('component_data', 'like', '%"component_id":'.$componentId.'%');
            })
            ->count();
    }

    private function morphRefs(int $componentId): array
    {
        $refs = [];
        $componentTypes = array_values(array_unique([
            Component::class,
            (new Component())->getMorphClass(),
        ]));

        if (Schema::hasTable('media')) {
            $refs['media.model_id'] = DB::table('media')
                ->whereIn('model_type', $componentTypes)
                ->where('model_id', $componentId)
                ->count();
        }

        if (Schema::hasTable('activity_log')) {
            $refs['activity_log.subject_id'] = DB::table('activity_log')
                ->whereIn('subject_type', $componentTypes)
                ->where('subject_id', $componentId)
                ->count();
        }

        return $refs;
    }

    private function groupSelect(string $key): string
    {
        return $key === 'manual-part-ipl'
            ? 'manual_id, part_number, ipl_num'
            : 'manual_id, ipl_num';
    }

    private function groupColumns(string $key): array
    {
        return $key === 'manual-part-ipl'
            ? ['manual_id', 'part_number', 'ipl_num']
            : ['manual_id', 'ipl_num'];
    }
}
