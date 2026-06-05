<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Start/Finish (phase) process rows need to carry their MasterRulePhaseRuleProcess
 * ids so documents attached to phase processes can be matched in the WO traveler.
 * Kept SEPARATE from rule_process_ids (Main) — the two id spaces collide.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            $table->json('phase_rule_process_ids')->nullable()->after('rule_process_ids');
        });
    }

    public function down(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            $table->dropColumn('phase_rule_process_ids');
        });
    }
};
