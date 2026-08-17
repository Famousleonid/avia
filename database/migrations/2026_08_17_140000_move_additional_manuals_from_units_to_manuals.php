<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manuals', function (Blueprint $table): void {
            $table->json('additional_manual_ids')->nullable()->after('reg_sb');
        });

        $validManualIds = DB::table('manuals')->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $validManualLookup = array_fill_keys($validManualIds, true);
        $assignments = [];

        DB::table('units')
            ->select(['id', 'manual_id', 'additional_manual_ids'])
            ->orderBy('id')
            ->chunk(500, function ($units) use (&$assignments, $validManualLookup): void {
                foreach ($units as $unit) {
                    $this->mergeAssignments(
                        $assignments,
                        (int) $unit->manual_id,
                        $unit->additional_manual_ids,
                        $validManualLookup
                    );
                }
            });

        DB::table('workorders')
            ->join('units', 'units.id', '=', 'workorders.unit_id')
            ->select([
                'workorders.id',
                'units.manual_id as primary_manual_id',
                'workorders.additional_manual_ids',
            ])
            ->orderBy('workorders.id')
            ->chunk(500, function ($workorders) use (&$assignments, $validManualLookup): void {
                foreach ($workorders as $workorder) {
                    $this->mergeAssignments(
                        $assignments,
                        (int) $workorder->primary_manual_id,
                        $workorder->additional_manual_ids,
                        $validManualLookup
                    );
                }
            });

        foreach ($assignments as $manualId => $additionalIds) {
            DB::table('manuals')->where('id', $manualId)->update([
                'additional_manual_ids' => json_encode(array_values(array_keys($additionalIds))),
            ]);
        }

        Schema::table('units', function (Blueprint $table): void {
            $table->dropColumn('additional_manual_ids');
        });

        Schema::table('workorders', function (Blueprint $table): void {
            $table->dropColumn('additional_manual_ids');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->json('additional_manual_ids')->nullable()->after('manual_id');
        });

        Schema::table('workorders', function (Blueprint $table): void {
            $table->json('additional_manual_ids')->nullable()->after('unit_id');
        });

        DB::table('manuals')
            ->select(['id', 'additional_manual_ids'])
            ->orderBy('id')
            ->chunkById(250, function ($manuals): void {
                foreach ($manuals as $manual) {
                    $json = json_encode($this->decodeIds($manual->additional_manual_ids));
                    DB::table('units')->where('manual_id', $manual->id)->update([
                        'additional_manual_ids' => $json,
                    ]);

                    $workorderIds = DB::table('workorders')
                        ->join('units', 'units.id', '=', 'workorders.unit_id')
                        ->where('units.manual_id', $manual->id)
                        ->pluck('workorders.id');
                    foreach ($workorderIds->chunk(500) as $ids) {
                        DB::table('workorders')->whereIn('id', $ids->all())->update([
                            'additional_manual_ids' => $json,
                        ]);
                    }
                }
            });

        Schema::table('manuals', function (Blueprint $table): void {
            $table->dropColumn('additional_manual_ids');
        });
    }

    /** @param array<int, array<int, true>> $assignments */
    private function mergeAssignments(
        array &$assignments,
        int $primaryManualId,
        mixed $rawIds,
        array $validManualLookup
    ): void {
        if ($primaryManualId <= 0) {
            return;
        }

        foreach ($this->decodeIds($rawIds) as $manualId) {
            if ($manualId <= 0 || $manualId === $primaryManualId || ! isset($validManualLookup[$manualId])) {
                continue;
            }

            $assignments[$primaryManualId][$manualId] = true;
        }
    }

    /** @return list<int> */
    private function decodeIds(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
};
