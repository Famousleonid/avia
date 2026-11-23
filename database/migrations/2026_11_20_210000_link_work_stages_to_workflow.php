<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_stages', function (Blueprint $table) {
            $table->string('workflow_key', 32)->nullable()->unique();
        });

        $stages = [
            'disassembly' => 'Disassembly',
            'wait_records' => 'Wait Records',
            'in_quotation' => 'In Quotation',
            'wait_approval' => 'Wait Approval',
            'ovh_in_process' => 'OVH in Process',
            'final_assembly_test' => 'Final Assembly & Test',
            'final_inspection' => 'Final Inspection',
        ];

        $order = 0;
        foreach ($stages as $key => $name) {
            $order += 10;
            $stage = DB::table('work_stages')->where('name', $name);
            if ($stage->exists()) {
                $stage->update(['workflow_key' => $key]);
            } else {
                DB::table('work_stages')->insert(['workflow_key' => $key, 'name' => $name, 'sort_order' => $order]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('work_stages', function (Blueprint $table) {
            $table->dropUnique(['workflow_key']);
            $table->dropColumn('workflow_key');
        });
    }
};
