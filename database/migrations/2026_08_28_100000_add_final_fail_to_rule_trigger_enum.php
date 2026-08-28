<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Final out of limits" trigger: fires on a FAIL of a final (after-repair)
 * measurement regardless of orig/wear limits — the final result is judged
 * against repair limits/steps, which dimensional triggers don't see. Meant
 * for EC rules ("repair out of tolerance → concession").
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE manual_parameter_rule_triggers MODIFY `trigger` ENUM('below_orig','above_orig','below_wear','above_wear','finding','finding_measurement','finding_inspection','finding_ndt','final_fail','manual') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("DELETE FROM manual_parameter_rule_triggers WHERE `trigger` = 'final_fail'");
        DB::statement("ALTER TABLE manual_parameter_rule_triggers MODIFY `trigger` ENUM('below_orig','above_orig','below_wear','above_wear','finding','finding_measurement','finding_inspection','finding_ndt','manual') NOT NULL");
    }
};
