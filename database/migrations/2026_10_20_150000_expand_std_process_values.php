<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Production already uses TEXT here; older local/test schemas may not.
        DB::statement('ALTER TABLE processes MODIFY process TEXT NOT NULL');
        foreach (['std_processes', 'workorder_std_process_items'] as $table) {
            DB::statement("ALTER TABLE {$table} MODIFY process VARCHAR(1000) NOT NULL DEFAULT '1'");
        }
    }

    public function down(): void
    {
        // Keep the shared process catalogue as TEXT, matching production.
        foreach (['std_processes', 'workorder_std_process_items'] as $table) {
            if (DB::table($table)->whereRaw('CHAR_LENGTH(process) > 255')->exists()) {
                throw new RuntimeException('Cannot shrink STD process values: long source specifications exist. No data was truncated.');
            }
        }
        foreach (['std_processes', 'workorder_std_process_items'] as $table) {
            DB::statement("ALTER TABLE {$table} MODIFY process VARCHAR(255) NOT NULL DEFAULT '1'");
        }
    }
};
