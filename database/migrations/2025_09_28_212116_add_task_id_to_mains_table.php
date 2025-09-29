<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('mains', function (Blueprint $table) {
            if (!Schema::hasColumn('mains', 'task_id')) {
                $table->unsignedBigInteger('task_id')->after('workorder_id');
                $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('mains', function (Blueprint $table) {
            if (Schema::hasColumn('mains', 'task_id')) {
                $table->dropForeign(['task_id']);
                $table->dropColumn('task_id');
            }
        });
    }
};
