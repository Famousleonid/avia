<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_dimension_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_dimension_point_id')
                ->constrained('manual_dimension_points')
                ->onDelete('cascade');
            // 'measurement' — dimensional limits; 'inspection' — visual defect check
            $table->enum('spec_type', ['measurement', 'inspection'])->default('measurement');
            // component being checked/measured
            $table->foreignId('component_id')->nullable()->constrained()->onDelete('set null');
            // for inspection specs: the specific defect code being checked
            $table->foreignId('codes_id')->nullable()->constrained('codes')->onDelete('set null');
            $table->string('description');
            $table->boolean('is_required')->default(true);

            // Overhaul limits (measurement only)
            $table->decimal('orig_dim_min', 8, 4)->nullable();
            $table->decimal('orig_dim_max', 8, 4)->nullable();

            // Repair limits (NULL = use overhaul limits as fallback; measurement only)
            $table->decimal('wear_dim_min', 8, 4)->nullable();
            $table->decimal('wear_dim_max', 8, 4)->nullable();

            $table->text('inspection')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('manual_dimension_specs');
        Schema::enableForeignKeyConstraints();
    }
};
