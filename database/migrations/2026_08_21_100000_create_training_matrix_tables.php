<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Матрица тренингов «как Excel MINIMUM REQUIREMENTS»:
 * training_categories — группы-секции файла (Landing Gear, Hydraulic Actuators, …);
 * training_matrix_rows — эталонные строки матрицы (1-я колонка = описание+PN из файла),
 * manual_id — привязка к заведённому CMM (2-я колонка); строка без привязки видна
 * как «CMM не заведён», тренинги возможны только через привязанный manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('training_matrix_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_category_id')->constrained('training_categories')->restrictOnDelete();
            $table->string('description')->nullable();
            $table->string('part_number');
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('manual_id')->nullable()->unique()->constrained('manuals')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_matrix_rows');
        Schema::dropIfExists('training_categories');
    }
};
