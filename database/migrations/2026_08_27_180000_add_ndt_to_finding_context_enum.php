<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * §4 gates: NDT verdicts are findings with their own context — a defect code on
 * a parameter may be discoverable by NDT (crack, inclusion), and finding_ndt
 * rule triggers match only those.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE manual_parameter_codes MODIFY finding_context ENUM('measurement','inspection','ndt') NOT NULL DEFAULT 'inspection'");
    }

    public function down(): void
    {
        DB::statement("UPDATE manual_parameter_codes SET finding_context = 'inspection' WHERE finding_context = 'ndt'");
        DB::statement("ALTER TABLE manual_parameter_codes MODIFY finding_context ENUM('measurement','inspection') NOT NULL DEFAULT 'inspection'");
    }
};
