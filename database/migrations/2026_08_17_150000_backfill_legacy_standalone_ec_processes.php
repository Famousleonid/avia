<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tdr_processes')
            || ! Schema::hasColumn('tdr_processes', 'standalone_ec_only')) {
            return;
        }

        $ecProcessNameId = DB::table('process_names')
            ->where('name', 'EC')
            ->value('id');

        if (! $ecProcessNameId) {
            return;
        }

        $ecSpecificationIds = DB::table('processes')
            ->where('process_names_id', $ecProcessNameId)
            ->pluck('id')
            ->mapWithKeys(fn ($id): array => [(int) $id => true])
            ->all();

        if ($ecSpecificationIds === []) {
            return;
        }

        DB::table('tdr_processes')
            ->where('process_names_id', $ecProcessNameId)
            ->where('standalone_ec_only', false)
            ->select(['id', 'processes'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($ecSpecificationIds): void {
                foreach ($rows as $row) {
                    $selectedIds = $this->normalizeStoredProcessIds($row->processes);
                    $containsExplicitEcSpecification = collect($selectedIds)
                        ->contains(fn (int $id): bool => isset($ecSpecificationIds[$id]));

                    if ($containsExplicitEcSpecification) {
                        DB::table('tdr_processes')
                            ->where('id', $row->id)
                            ->update(['standalone_ec_only' => true]);
                    }
                }
            });
    }

    /**
     * Decode current JSON and legacy double-encoded JSON values.
     *
     * @return list<int>
     */
    private function normalizeStoredProcessIds(mixed $raw): array
    {
        for ($depth = 0; $depth < 3 && is_string($raw); $depth++) {
            $trimmed = trim($raw);
            if ($trimmed === '') {
                return [];
            }

            $decoded = json_decode($trimmed, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                break;
            }

            $raw = $decoded;
        }

        if (! is_array($raw)) {
            $raw = [$raw];
        }

        return collect($raw)
            ->map(function ($value): int {
                if (is_array($value) && array_key_exists('id', $value)) {
                    return (int) $value['id'];
                }

                return (int) $value;
            })
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function down(): void
    {
        // Data correction is intentionally not reverted: after deployment these
        // rows are indistinguishable from newly created legitimate standalone EC.
    }
};
