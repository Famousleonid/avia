<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workorder_std_processes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workorder_id')->constrained('workorders')->cascadeOnDelete();
            $table->string('std_type', 16);
            $table->foreignId('process_name_id')->constrained('process_names')->cascadeOnDelete();
            $table->foreignId('source_tdr_id')->nullable()->constrained('tdrs')->nullOnDelete();
            $table->foreignId('source_tdr_process_id')->nullable()->constrained('tdr_processes')->nullOnDelete();
            $table->json('processes')->nullable();
            $table->string('description')->nullable();
            $table->string('notes')->nullable();
            $table->string('repair_order')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->date('date_start')->nullable();
            $table->foreignId('date_start_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_finish')->nullable();
            $table->foreignId('date_finish_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_promise')->nullable();
            $table->boolean('ignore_row')->default(false);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['workorder_id', 'process_name_id'], 'workorder_std_process_unique');
            $table->index(['workorder_id', 'std_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workorder_std_processes');
    }
};
