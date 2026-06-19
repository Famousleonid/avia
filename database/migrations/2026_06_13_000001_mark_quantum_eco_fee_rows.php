<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quantum_ro_lines')) {
            return;
        }

        DB::table('quantum_ro_lines')
            ->select(['id', 'pn', 'source_hash'])
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $normalizedPn = preg_replace('/[\s_-]+/', '', strtoupper(trim((string) $row->pn)));

                    if ($normalizedPn !== 'ECOFEE') {
                        continue;
                    }

                    DB::table('quantum_ro_lines')
                        ->where('id', $row->id)
                        ->update([
                            'apply_status' => 'ECO FEE',
                            'apply_message' => 'ECO FEE row, no avia target needed',
                            'applied_target_table' => null,
                            'applied_target_id' => null,
                            'applied_source_hash' => $row->source_hash,
                            'applied_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Data migration only; previous user-dismissed states cannot be restored safely.
    }
};
