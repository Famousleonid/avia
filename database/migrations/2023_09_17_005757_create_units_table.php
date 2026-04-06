<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Выполняется до create_workorders (005823): workorders.unit_id → units.id.
 * Соответствует базовым полям модели Unit: part_number, verified, manual_id (+ timestamps).
 * eff_code, name, description — миграции 2025_08_27, 2025_11_18.
 * Составной unique (manual_id, part_number) — 2026_03_11_000001_fix_units_unique_constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique();
            $table->boolean('verified')->default(false);
            $table->foreignId('manual_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
