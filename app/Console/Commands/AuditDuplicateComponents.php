<?php

namespace App\Console\Commands;

use App\Models\Component;
use App\Models\LogCard;
use App\Models\Manual;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditDuplicateComponents extends Command
{
    protected $signature = 'components:audit-duplicates
        {--key=manual-part-ipl : Duplicate key: manual-part-ipl or manual-ipl}
        {--manual= : Limit to one manual id}
        {--limit=100 : Max duplicate groups to print}
        {--with-trashed : Include soft-deleted components}
        {--json : Output machine-readable JSON}';

    protected $description = 'Read-only audit of duplicate components and their references.';

    public function handle(): int
    {
        $key = (string) $this->option('key');
        if (! in_array($key, ['manual-part-ipl', 'manual-ipl'], true)) {
            $this->error('Invalid --key. Use manual-part-ipl or manual-ipl.');

            return self::FAILURE;
        }

        $groups = $this->duplicateGroups($key);
        $limit = max(1, (int) $this->option('limit'));
        $reports = $groups->take($limit)->map(fn ($group) => $this->buildGroupReport($group, $key))->values();
        $summary = $this->summary($groups);

        if ($this->option('json')) {
            $this->line(json_encode([
                'summary' => $summary,
                'groups' => $reports,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Components total: '.$summary['components_total']);
        $this->info('Duplicate groups found: '.$summary['duplicate_groups'].' (showing '.$reports->count().')');
        $this->info('Components in duplicate groups: '.$summary['duplicate_rows']);
        $this->info('Extra duplicate components to merge/remove: '.$summary['extra_duplicates']);
        $this->line('Key: '.$key);

        foreach ($reports as $report) {
            $this->newLine();
            $this->warn(sprintf(
                'Manual %s %s | PN: %s | IPL: %s | Qty: %d',
                $report['manual_id'],
                $report['manual'],
                $report['part_number'] ?? '*',
                $report['ipl_num'] ?? '*',
                $report['qty']
            ));

            $rows = collect($report['components'])->map(fn ($component) => [
                'id' => $component['id'],
                'name' => $component['name'],
                'pn' => $component['part_number'],
                'ipl' => $component['ipl_num'],
                'log' => $component['log_card'] ? 'yes' : 'no',
                'repair' => $component['repair'] ? 'yes' : 'no',
                'refs' => $component['total_refs'],
                'ref detail' => $this->formatRefs($component['refs']),
            ])->all();

            $this->table(['id', 'name', 'pn', 'ipl', 'log', 'repair', 'refs', 'ref detail'], $rows);

            if ($report['suggested_canonical_id']) {
                $this->line('Suggested canonical by most refs: '.$report['suggested_canonical_id']);
            }
        }

        return self::SUCCESS;
    }

    private function summary(Collection $groups): array
    {
        $duplicateRows = (int) $groups->sum('qty');

        return [
            'components_total' => Component::query()->whereNull('deleted_at')->count(),
            'duplicate_groups' => $groups->count(),
            'duplicate_rows' => $duplicateRows,
            'extra_duplicates' => $duplicateRows - $groups->count(),
        ];
    }

    private function duplicateGroups(string $key): Collection
    {
        $query = ($this->option('with-trashed') ? Component::withTrashed() : Component::query())
            ->selectRaw($this->groupSelect($key).', COUNT(*) as qty')
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

    private function buildGroupReport(object $group, string $key): array
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
                    'manual_id' => (int) $component->manual_id,
                    'log_card' => (bool) $component->log_card,
                    'repair' => (bool) $component->repair,
                    'refs' => $refs,
                    'total_refs' => array_sum($refs),
                    'created_at' => (string) $component->created_at,
                    'updated_at' => (string) $component->updated_at,
                ];
            })
            ->values();

        $suggested = $components
            ->sortByDesc('total_refs')
            ->first();

        return [
            'manual_id' => (int) $group->manual_id,
            'manual' => trim(($manual?->number ?? '-').' ('.($manual?->lib ?? '-').')'),
            'part_number' => $group->part_number ?? null,
            'ipl_num' => $group->ipl_num ?? null,
            'qty' => (int) $group->qty,
            'suggested_canonical_id' => $suggested && $suggested['total_refs'] > 0 ? $suggested['id'] : null,
            'components' => $components->all(),
        ];
    }

    private function componentsForGroup(object $group, string $key): Collection
    {
        $query = ($this->option('with-trashed') ? Component::withTrashed() : Component::query())
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

    private function formatRefs(array $refs): string
    {
        if ($refs === []) {
            return '-';
        }

        return collect($refs)
            ->map(fn ($count, $key) => $key.':'.$count)
            ->implode(', ');
    }
}
