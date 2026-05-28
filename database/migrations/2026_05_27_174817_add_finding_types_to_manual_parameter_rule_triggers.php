<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE manual_parameter_rule_triggers
            MODIFY COLUMN `trigger`
            ENUM('below_orig','above_orig','below_wear','above_wear','finding','finding_measurement','finding_inspection','manual')
            NOT NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE manual_parameter_rule_triggers
            SET `trigger` = 'finding'
            WHERE `trigger` IN ('finding_measurement','finding_inspection')");

        DB::statement("ALTER TABLE manual_parameter_rule_triggers
            MODIFY COLUMN `trigger`
            ENUM('below_orig','above_orig','below_wear','above_wear','finding','manual')
            NOT NULL");
    }
};
