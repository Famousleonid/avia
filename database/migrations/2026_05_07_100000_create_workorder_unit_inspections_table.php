<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workorder_unit_inspections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workorder_id')->constrained('workorders')->cascadeOnDelete();
            $table->foreignId('condition_id')->nullable()->constrained('conditions')->nullOnDelete();
            $table->foreignId('source_tdr_id')->nullable()->constrained('tdrs')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->unsignedInteger('qty')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('assy_serial_number')->nullable();
            $table->boolean('use_tdr')->default(true);
            $table->boolean('use_process_forms')->default(false);
            $table->timestamp('source_deleted_at')->nullable();
            $table->timestamps();

            $table->unique(['workorder_id', 'condition_id'], 'workorder_unit_inspection_unique');
            $table->index(['workorder_id', 'source_tdr_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workorder_unit_inspections');
    }
};
