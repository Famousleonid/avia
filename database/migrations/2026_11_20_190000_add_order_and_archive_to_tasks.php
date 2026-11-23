<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('archived_at')->nullable();
        });

        $orders = [];
        foreach (DB::table('tasks')->orderBy('general_task_id')->orderBy('name')->orderBy('id')->get(['id', 'general_task_id']) as $task) {
            $order = ($orders[$task->general_task_id] ?? 0) + 10;
            DB::table('tasks')->where('id', $task->id)->update(['sort_order' => $order]);
            $orders[$task->general_task_id] = $order;
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['sort_order', 'archived_at']);
        });
    }
};
