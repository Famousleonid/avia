<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ndt_cad_csv');
    }

    public function down(): void
    {
        Schema::create('ndt_cad_csv', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workorder_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('ndt_components')->nullable();
            $table->json('cad_components')->nullable();
            $table->json('stress_components')->nullable();
            $table->json('paint_components')->nullable();
            $table->timestamps();
        });
    }
};
