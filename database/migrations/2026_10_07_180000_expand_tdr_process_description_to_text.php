<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Template descriptions already allow long technical instructions.
        DB::statement('ALTER TABLE tdr_processes MODIFY description TEXT NULL');
    }

    public function down(): void
    {
        if (DB::table('tdr_processes')->whereRaw('CHAR_LENGTH(description) > 255')->exists()) {
            throw new RuntimeException('Cannot shrink TDR descriptions: values longer than 255 characters exist. No data was truncated.');
        }
        DB::statement('ALTER TABLE tdr_processes MODIFY description VARCHAR(255) NULL');
    }
};
