<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workorder_std_process_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workorder_id')->constrained('workorders')->cascadeOnDelete();
            $table->foreignId('component_id')->nullable()->constrained('components')->nullOnDelete();
            $table->foreignId('std_process_id')->nullable()->constrained('std_processes')->nullOnDelete();
            $table->string('std_type', 16);
            $table->string('ipl_num', 64)->default('');
            $table->string('part_number')->default('');
            $table->text('description')->nullable();
            $table->string('process')->default('1');
            $table->unsignedInteger('base_qty')->default(1);
            $table->unsignedInteger('excluded_qty')->default(0);
            $table->unsignedInteger('remaining_qty')->default(1);
            $table->string('manual')->nullable();
            $table->string('eff_code')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['workorder_id', 'std_type'], 'wo_std_items_workorder_type_index');
            $table->index(['workorder_id', 'component_id'], 'wo_std_items_workorder_component_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workorder_std_process_items');
    }
};
