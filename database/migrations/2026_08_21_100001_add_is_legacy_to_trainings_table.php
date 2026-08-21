<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * is_legacy — «Old training»: обучение было в прошлом (до системы), дата неизвестна.
 * Запись без даты и без форм 112/132; в матрице отображается как «X».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->boolean('is_legacy')->default(false)->after('form_type');
        });
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn('is_legacy');
        });
    }
};
