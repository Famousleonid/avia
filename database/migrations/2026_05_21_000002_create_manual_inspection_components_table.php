<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_inspection_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->constrained('manuals')->onDelete('cascade');
            $table->string('label');           // e.g. "Main Fitting LH", "Bushing"
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('manual_inspection_component_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inspection_component_id');
            $table->foreignId('component_id')->constrained('components')->onDelete('cascade');
            $table->timestamps();

            $table->foreign('inspection_component_id', 'mic_variants_ic_fk')
                ->references('id')->on('manual_inspection_components')
                ->onDelete('cascade');

            $table->unique(['inspection_component_id', 'component_id'], 'mic_variants_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_inspection_component_variants');
        Schema::dropIfExists('manual_inspection_components');
    }
};
