<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('manuals')
            ->select('id')
            ->whereNull('revision_number')
            ->orderBy('id')
            ->chunkById(200, function ($manuals): void {
                foreach ($manuals as $manual) {
                    $revisionNumber = DB::table('manual_revision_checks')
                        ->where('manual_id', $manual->id)
                        ->orderByDesc('revision_date')
                        ->orderByDesc('id')
                        ->value('revision_number');

                    if ($revisionNumber !== null && trim((string) $revisionNumber) !== '') {
                        DB::table('manuals')
                            ->where('id', $manual->id)
                            ->update(['revision_number' => trim((string) $revisionNumber)]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Backfilled values become canonical manual data and are intentionally retained.
    }
};
