<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_assemblies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_id')->constrained('components')->cascadeOnDelete();
            $table->string('assy_part_number', 100);
            $table->string('assy_ipl_num', 50)->nullable();
            $table->string('units_assy', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['component_id', 'sort_order'], 'component_assemblies_component_sort_idx');
            $table->index('assy_part_number', 'component_assemblies_assy_part_number_idx');
            $table->unique(
                ['component_id', 'assy_part_number', 'assy_ipl_num'],
                'component_assemblies_component_assy_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_assemblies');
    }
};
