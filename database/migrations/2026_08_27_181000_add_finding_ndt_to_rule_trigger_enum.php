<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** §4 gates: finding_ndt trigger — a rule activated by an NDT verdict. */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE manual_parameter_rule_triggers MODIFY `trigger` ENUM('below_orig','above_orig','below_wear','above_wear','finding','finding_measurement','finding_inspection','finding_ndt','manual') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("DELETE FROM manual_parameter_rule_triggers WHERE `trigger` = 'finding_ndt'");
        DB::statement("ALTER TABLE manual_parameter_rule_triggers MODIFY `trigger` ENUM('below_orig','above_orig','below_wear','above_wear','finding','finding_measurement','finding_inspection','manual') NOT NULL");
    }
};
