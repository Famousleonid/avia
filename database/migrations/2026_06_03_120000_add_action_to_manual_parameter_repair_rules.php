<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * EC step 1: rule outcome becomes a 3-way action instead of the order_replacement bool.
 *   action: 'repair' | 'order_new' | 'ec'
 * Action and trigger are orthogonal (any action with any trigger).
 * Backfilled from order_replacement (true -> order_new, false -> repair). The legacy
 * bool column is kept (vestigial) to avoid breaking anything during the transition.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_parameter_repair_rules', function (Blueprint $table) {
            $table->string('action', 20)->default('repair')->after('order_replacement');
        });

        DB::table('manual_parameter_repair_rules')
            ->where('order_replacement', true)
            ->update(['action' => 'order_new']);
    }

    public function down(): void
    {
        Schema::table('manual_parameter_repair_rules', function (Blueprint $table) {
            $table->dropColumn('action');
        });
    }
};
