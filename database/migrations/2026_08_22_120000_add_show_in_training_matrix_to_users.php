<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * show_in_training_matrix — явное включение сотрудника в колонки матрицы
 * тренингов (управляется модалкой Personnel на show_all, Admin/Manager).
 * Бэкфилл повторяет прежнее неявное правило: производственные роли со stamp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('show_in_training_matrix')->default(false)->after('stamp');
        });

        $roleIds = DB::table('roles')->whereIn('name', ['Technician', 'Team Leader', 'Paint'])->pluck('id');
        DB::table('users')
            ->whereIn('role_id', $roleIds)
            ->whereNotNull('stamp')
            ->where('stamp', '<>', '')
            ->where('is_admin', false)
            ->whereNull('deleted_at')
            ->update(['show_in_training_matrix' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('show_in_training_matrix');
        });
    }
};
