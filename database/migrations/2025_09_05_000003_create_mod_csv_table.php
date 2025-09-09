<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mod_csv', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workorder_id')->constrained()->onDelete('cascade');
            $table->json('ndt_components')->nullable()->comment('Список компонентов для NDT в JSON формате');
            $table->json('cad_components')->nullable()->comment('Список компонентов для CAD в JSON формате');
            $table->timestamps();
            
            // Уникальный индекс для workorder_id (один workorder = одна запись ModCsv)
            $table->unique('workorder_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mod_csv');
    }
};
