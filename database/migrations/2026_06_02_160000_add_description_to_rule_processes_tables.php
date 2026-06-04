<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-process notes/description in repair rules and master-rule phases.
 * Each process line in a rule (= a point) can carry its own note, e.g.
 * machining on hole 1 → "fig. 6039", machining on hole 2 → "fig. 6041".
 *
 * Tables:
 *  - manual_parameter_rule_processes   — ACTIVE Main rule processes (ManualParameterRuleProcess)
 *  - master_rule_phase_rule_processes  — Start/Finish phase processes
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_parameter_rule_processes', function (Blueprint $table) {
            $table->string('description', 255)->nullable()->after('manual_process_id');
        });

        Schema::table('master_rule_phase_rule_processes', function (Blueprint $table) {
            $table->string('description', 255)->nullable()->after('manual_process_id');
        });
    }

    public function down(): void
    {
        Schema::table('manual_parameter_rule_processes', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('master_rule_phase_rule_processes', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
