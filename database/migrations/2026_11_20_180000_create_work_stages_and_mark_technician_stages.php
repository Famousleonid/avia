<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_tasks', function (Blueprint $table) {
            $table->string('technician_stage', 24)->nullable()->unique();
        });

        foreach (['disassembly', 'assembly'] as $stage) {
            $id = DB::table('general_tasks')->whereRaw('LOWER(TRIM(name)) = ?', [$stage])
                ->orderBy('sort_order')->orderBy('id')->value('id');
            if ($id !== null) {
                DB::table('general_tasks')->where('id', $id)->update(['technician_stage' => $stage]);
            }
        }

        Schema::create('work_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 250)->unique();
            $table->unsignedInteger('sort_order');
        });

        $stages = [
            'Disassembly', 'Wait Records', 'In Quotation', 'Wait Approval',
            'OVH in Process', 'Final Assembly & Test', 'Final Inspection',
        ];
        DB::table('work_stages')->insert(array_map(
            fn ($index, $name) => ['name' => $name, 'sort_order' => ($index + 1) * 10],
            array_keys($stages),
            $stages
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('work_stages');
        Schema::table('general_tasks', function (Blueprint $table) {
            $table->dropUnique(['technician_stage']);
            $table->dropColumn('technician_stage');
        });
    }
};
