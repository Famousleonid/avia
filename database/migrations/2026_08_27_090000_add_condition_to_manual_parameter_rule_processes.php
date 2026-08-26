<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conditional processes inside a repair rule (docs/repair-routes-and-gates.md §8b).
 * A rule-process row may carry a condition evaluated against the MERGED plan:
 *   {"type":"has_process","process_name_ids":[..]}     — row applies only when
 *       the plan (unconditional rows of all matched rules) contains one of these
 *   {"type":"not_has_process","process_name_ids":[..]} — only when it contains none
 * Null → always applies (old behavior).
 * Case: Silver route runs NDT-4 BEFORE silver when chrome is not redone, but the
 * shared NDT-4 goes AFTER silver when a chrome route is in the same plan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_parameter_rule_processes', function (Blueprint $table) {
            $table->json('condition')->nullable()->after('is_gate');
        });
    }

    public function down(): void
    {
        Schema::table('manual_parameter_rule_processes', function (Blueprint $table) {
            $table->dropColumn('condition');
        });
    }
};
