<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_repair_procedure_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_repair_procedure_id')
                ->constrained('manual_repair_procedures')
                ->onDelete('cascade');
            $table->foreignId('process_name_id')
                ->constrained('process_names')
                ->onDelete('restrict');
            $table->foreignId('manual_dimension_figure_id')
                ->nullable()
                ->constrained('manual_dimension_figures')
                ->onDelete('set null');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('manual_repair_procedure_steps');
        Schema::enableForeignKeyConstraints();
    }
};
