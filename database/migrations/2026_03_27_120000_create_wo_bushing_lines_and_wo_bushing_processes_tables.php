<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Нормализованное хранение бушингов: линии (одна строка на каждую партию в WO, в т.ч. дубликаты партномера)
     * и назначения процессов с количеством и датами.
     */
    public function up(): void
    {
        Schema::create('wo_bushing_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wo_bushing_id')->constrained('wo_bushings')->cascadeOnDelete();
            $table->foreignId('workorder_id')->constrained('workorders')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('components')->cascadeOnDelete();
            $table->unsignedInteger('qty');
            /** Остаток по линии (не распределён по процессам или ещё не ушёл в цех) */
            $table->unsignedInteger('qty_remaining');
            /** Ключ группы из формы — различает несколько одинаковых компонентов в одном WO */
            $table->string('group_key', 64)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['workorder_id', 'component_id']);
            $table->index('wo_bushing_id');
        });

        Schema::create('wo_bushing_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wo_bushing_line_id')->constrained('wo_bushing_lines')->cascadeOnDelete();
            $table->foreignId('process_id')->constrained('processes')->cascadeOnDelete();
            /** Сколько единиц с этой линии учитываются на данном процессе */
            $table->unsignedInteger('qty');
            $table->date('date_start')->nullable();
            $table->date('date_finish')->nullable();
            $table->timestamps();

            $table->index('wo_bushing_line_id');
            $table->index('process_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wo_bushing_processes');
        Schema::dropIfExists('wo_bushing_lines');
    }
};
