<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * is_active — юнит в работе. Снятый флаг скрывает строку из матрицы
 * (аналог скрытых/штрихованных строк Excel-файла), не удаляя её.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_matrix_rows', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('manual_id');
        });
    }

    public function down(): void
    {
        Schema::table('training_matrix_rows', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
