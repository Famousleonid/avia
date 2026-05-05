<?php

namespace App\Console\Commands;

use App\Models\Component;
use App\Models\LogCard;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditComponentReferences extends Command
{
    protected $signature = 'components:audit-references
        {--limit=50 : Max rows to show per problem bucket}
        {--with-trashed-sources : Include soft-deleted referencing rows such as deleted TDRs}
        {--json : Output machine-readable JSON}';

    protected $description = 'Read-only audit for component references pointing to missing or soft-deleted components.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $report = [
            'direct' => $this->directReferences($limit),
            'log_cards' => $this->logCardReferences($limit),
            'morph' => $this->morphReferences($limit),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->printBucket('Direct component_id/order_component_id references', $report['direct']);
        $this->printBucket('Log card component_data references', $report['log_cards']);
        $this->printBucket('Morph media/activity references', $report['morph']);

        return self::SUCCESS;
    }

    private function directReferences(int $limit): array
    {
        $results = [];

        foreach ($this->componentReferenceColumns() as $table => $columns) {
            foreach ($columns as $column) {
                $missing = $this->missingDirectRows($table, $column, $limit);
                $trashed = $this->trashedDirectRows($table, $column, $limit);

                if ($missing['count'] > 0 || $trashed['count'] > 0) {
                    $results[] = [
                        'source' => $table.'.'.$column,
                        'missing_count' => $missing['count'],
                        'missing_examples' => $missing['examples'],
                        'trashed_count' => $trashed['count'],
                        'trashed_examples' => $trashed['examples'],
                    ];
                }
            }
        }

        return $results;
    }

    private function missingDirectRows(string $table, string $column, int $limit): array
    {
        $base = DB::table($table)
            ->leftJoin('components', $table.'.'.$column, '=', 'components.id')
            ->whereNotNull($table.'.'.$column)
            ->whereNull('components.id');
        $base = $this->withoutTrashedSource($base, $table);

        return [
            'count' => (clone $base)->count(),
            'examples' => (clone $base)
                ->limit($limit)
                ->get([$table.'.id as row_id', $table.'.'.$column.' as component_id'])
                ->map(fn ($row) => (array) $row)
                ->all(),
        ];
    }

    private function trashedDirectRows(string $table, string $column, int $limit): array
    {
        $base = DB::table($table)
            ->join('components', $table.'.'.$column, '=', 'components.id')
            ->whereNotNull($table.'.'.$column)
            ->whereNotNull('components.deleted_at');
        $base = $this->withoutTrashedSource($base, $table);

        return [
            'count' => (clone $base)->count(),
            'examples' => (clone $base)
                ->limit($limit)
                ->get([$table.'.id as row_id', $table.'.'.$column.' as component_id'])
                ->map(fn ($row) => (array) $row)
                ->all(),
        ];
    }

    private function logCardReferences(int $limit): array
    {
        if (! Schema::hasTable('log_cards')) {
            return [];
        }

        $missing = [];
        $trashed = [];
        $knownComponents = Component::withTrashed()
            ->get(['id', 'deleted_at'])
            ->keyBy('id');

        LogCard::query()
            ->whereNotNull('component_data')
            ->chunkById(100, function (Collection $logCards) use ($knownComponents, &$missing, &$trashed, $limit) {
                foreach ($logCards as $logCard) {
                    $data = json_decode((string) $logCard->component_data, true);
                    if (! is_array($data)) {
                        continue;
                    }

                    foreach ($this->componentIdsFromArray($data) as $componentId) {
                        $component = $knownComponents->get($componentId);
                        if (! $component) {
                            $missing[] = ['log_card_id' => $logCard->id, 'component_id' => $componentId];
                        } elseif ($component->deleted_at !== null) {
                            $trashed[] = ['log_card_id' => $logCard->id, 'component_id' => $componentId];
                        }

                        if (count($missing) >= $limit && count($trashed) >= $limit) {
                            return false;
                        }
                    }
                }
            });

        return [
            [
                'source' => 'log_cards.component_data',
                'missing_count' => count($missing),
                'missing_examples' => array_slice($missing, 0, $limit),
                'trashed_count' => count($trashed),
                'trashed_examples' => array_slice($trashed, 0, $limit),
            ],
        ];
    }

    private function componentIdsFromArray(array $data): array
    {
        $ids = [];
        foreach ($data as $key => $value) {
            if ($key === 'component_id' && filled($value) && is_numeric($value)) {
                $ids[] = (int) $value;
            }

            if (is_array($value)) {
                $ids = array_merge($ids, $this->componentIdsFromArray($value));
            }
        }

        return array_values(array_unique($ids));
    }

    private function morphReferences(int $limit): array
    {
        $results = [];
        $types = array_values(array_unique([
            Component::class,
            (new Component())->getMorphClass(),
        ]));

        foreach ([
            ['media', 'model_type', 'model_id'],
            ['activity_log', 'subject_type', 'subject_id'],
        ] as [$table, $typeColumn, $idColumn]) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $missing = $this->missingMorphRows($table, $typeColumn, $idColumn, $types, $limit);
            $trashed = $this->trashedMorphRows($table, $typeColumn, $idColumn, $types, $limit);

            if ($missing['count'] > 0 || $trashed['count'] > 0) {
                $results[] = [
                    'source' => $table.'.'.$idColumn,
                    'missing_count' => $missing['count'],
                    'missing_examples' => $missing['examples'],
                    'trashed_count' => $trashed['count'],
                    'trashed_examples' => $trashed['examples'],
                ];
            }
        }

        return $results;
    }

    private function missingMorphRows(string $table, string $typeColumn, string $idColumn, array $types, int $limit): array
    {
        $base = DB::table($table)
            ->leftJoin('components', $table.'.'.$idColumn, '=', 'components.id')
            ->whereIn($table.'.'.$typeColumn, $types)
            ->whereNotNull($table.'.'.$idColumn)
            ->whereNull('components.id');
        $base = $this->withoutTrashedSource($base, $table);

        return [
            'count' => (clone $base)->count(),
            'examples' => (clone $base)
                ->limit($limit)
                ->get([$table.'.id as row_id', $table.'.'.$idColumn.' as component_id'])
                ->map(fn ($row) => (array) $row)
                ->all(),
        ];
    }

    private function trashedMorphRows(string $table, string $typeColumn, string $idColumn, array $types, int $limit): array
    {
        $base = DB::table($table)
            ->join('components', $table.'.'.$idColumn, '=', 'components.id')
            ->whereIn($table.'.'.$typeColumn, $types)
            ->whereNotNull($table.'.'.$idColumn)
            ->whereNotNull('components.deleted_at');
        $base = $this->withoutTrashedSource($base, $table);

        return [
            'count' => (clone $base)->count(),
            'examples' => (clone $base)
                ->limit($limit)
                ->get([$table.'.id as row_id', $table.'.'.$idColumn.' as component_id'])
                ->map(fn ($row) => (array) $row)
                ->all(),
        ];
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

    private function withoutTrashedSource($query, string $table)
    {
        if (! $this->option('with-trashed-sources') && Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull($table.'.deleted_at');
        }

        return $query;
    }

    private function printBucket(string $title, array $items): void
    {
        $this->newLine();
        $this->warn($title);

        if ($items === []) {
            $this->info('OK: no problems found.');

            return;
        }

        $this->table(
            ['source', 'missing', 'missing examples', 'soft-deleted', 'soft-deleted examples'],
            collect($items)->map(fn ($item) => [
                'source' => $item['source'],
                'missing' => $item['missing_count'],
                'missing examples' => $this->formatExamples($item['missing_examples']),
                'soft-deleted' => $item['trashed_count'],
                'soft-deleted examples' => $this->formatExamples($item['trashed_examples']),
            ])->all()
        );
    }

    private function formatExamples(array $examples): string
    {
        if ($examples === []) {
            return '-';
        }

        return collect($examples)
            ->take(5)
            ->map(fn ($row) => collect($row)->map(fn ($value, $key) => $key.'='.$value)->implode(' '))
            ->implode('; ');
    }
}
