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
        Schema::create('ndt_cad_csv', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workorder_id')->constrained()->onDelete('cascade');
            $table->json('ndt_components')->nullable();
            $table->json('cad_components')->nullable();
            $table->timestamps();

            // Уникальный индекс для связи один-к-одному
            $table->unique('workorder_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ndt_cad_csv');
    }
};
