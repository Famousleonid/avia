<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workorder_general_task_statuses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workorder_id')->constrained('workorders')->cascadeOnDelete();
            $table->foreignId('general_task_id')->constrained('general_tasks')->cascadeOnDelete();

            $table->boolean('is_done')->default(false);
            $table->timestamp('done_at')->nullable();
            $table->foreignId('done_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['workorder_id', 'general_task_id'], 'wo_gt_unique');
            $table->index(['workorder_id', 'general_task_id', 'is_done'], 'wo_gt_done_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workorder_general_task_statuses');
    }
};
