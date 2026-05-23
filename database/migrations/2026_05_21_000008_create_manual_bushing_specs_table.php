<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // calculated_oversize = measured_hole + interference_value,
        // then rounded per oversize_rounding rule.
        Schema::create('manual_bushing_specs', function (Blueprint $table) {
            $table->id();
            // spec that measures the bore/hole (source for oversize calculation)
            $table->foreignId('hole_spec_id')
                ->constrained('manual_dimension_specs')
                ->onDelete('cascade');
            // spec that defines the bushing OD tolerance
            $table->unsignedBigInteger('bushing_od_spec_id')->nullable();
            // for same_hole/opposing pairs — references the other bushing's bushing_spec
            $table->unsignedBigInteger('paired_bushing_spec_id')->nullable();

            $table->enum('arrangement', ['sequential', 'same_hole', 'opposing']);
            $table->decimal('interference_value', 6, 4)->default(0);
            $table->decimal('oversize_step', 6, 4);
            $table->decimal('max_oversize', 6, 4);
            // ceil = always round up (safest); nearest = nearest catalog option; exact = must match exactly
            $table->enum('oversize_rounding', ['ceil', 'nearest', 'exact'])->default('ceil');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('bushing_od_spec_id')
                ->references('id')->on('manual_dimension_specs')
                ->onDelete('set null');

            $table->foreign('paired_bushing_spec_id')
                ->references('id')->on('manual_bushing_specs')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('manual_bushing_specs');
        Schema::enableForeignKeyConstraints();
    }
};
