<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Plan-order priority for the repair-plan merger (docs/repair-routes-and-gates.md §2).
 * Route chains stay hard constraints; among EQUALLY READY nodes the topological
 * sort prefers lower plan_order. Null = default 100. Shop logistics: machining /
 * stress relief / paint are in-house, most other processes are at vendors —
 * machining runs first so the part ships out once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_names', function (Blueprint $table) {
            $table->integer('plan_order')->nullable()->after('scope');
        });

        DB::table('process_names')
            ->whereIn('name', ['Machining', 'Machining (EC)', 'Machining(EC)'])
            ->update(['plan_order' => 10]);
    }

    public function down(): void
    {
        Schema::table('process_names', function (Blueprint $table) {
            $table->dropColumn('plan_order');
        });
    }
};
