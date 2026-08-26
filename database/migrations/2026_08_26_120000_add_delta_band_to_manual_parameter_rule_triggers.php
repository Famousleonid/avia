<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Threshold (delta band) on dimensional triggers — docs/repair-routes-and-gates.md §8a.
 * Exceedance = how far the measured value is past the limit (above max / below min).
 * A dimensional trigger fires when exceedance > min_delta (when set) and
 * exceedance <= max_delta (when set). Both null → any exceedance (old behavior).
 * Example: silver restores up to 0.010" → Silver rule above_orig max_delta=0.010,
 * Chrome-in-hole rule above_orig min_delta=0.010.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_parameter_rule_triggers', function (Blueprint $table) {
            $table->decimal('min_delta', 8, 4)->nullable()->after('codes_id');
            $table->decimal('max_delta', 8, 4)->nullable()->after('min_delta');
        });
    }

    public function down(): void
    {
        Schema::table('manual_parameter_rule_triggers', function (Blueprint $table) {
            $table->dropColumn(['min_delta', 'max_delta']);
        });
    }
};
