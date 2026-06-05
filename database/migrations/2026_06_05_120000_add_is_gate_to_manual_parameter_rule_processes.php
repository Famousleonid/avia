<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EC gate anchor: one process in a repair rule may be flagged as the gate
 * (typically NDT). When the part goes to EC, everything AFTER the gate process
 * (by sort_order) is frozen until the OEM concession is granted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_parameter_rule_processes', function (Blueprint $table) {
            $table->boolean('is_gate')->default(false)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('manual_parameter_rule_processes', function (Blueprint $table) {
            $table->dropColumn('is_gate');
        });
    }
};
