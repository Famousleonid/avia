<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('manual_parameter_codes', 'finding_context')) {
            return;
        }

        DB::statement("ALTER TABLE manual_parameter_codes
            ADD COLUMN `finding_context`
            ENUM('measurement','inspection') NOT NULL DEFAULT 'inspection'
            AFTER `codes_id`");
    }

    public function down(): void
    {
        if (Schema::hasColumn('manual_parameter_codes', 'finding_context')) {
            DB::statement("ALTER TABLE manual_parameter_codes DROP COLUMN `finding_context`");
        }
    }
};
