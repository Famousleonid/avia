<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per measurement attempt.
        // stage=initial: before repair; stage=final: after repair.
        // replaces_id chains: final → initial (or intermediate) FAIL.
        // actual_value is nullable: damage visible without measurement (corrosion, crack).
        // result is nullable: no tolerance check when actual_value is NULL.
        // limits_source: 'wear' if instruction=Repair AND spec.wear_dim IS NOT NULL, else 'orig'.
        Schema::create('wo_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wo_measurement_session_id')
                ->constrained('wo_measurement_sessions')
                ->onDelete('cascade');
            $table->foreignId('manual_dimension_spec_id')
                ->constrained('manual_dimension_specs')
                ->onDelete('restrict');
            $table->enum('stage', ['initial', 'final'])->default('initial');
            // final measurement references the initial FAIL it corrects (chain for multiple cycles)
            $table->unsignedBigInteger('replaces_id')->nullable();

            $table->decimal('actual_value', 8, 4)->nullable();
            $table->enum('limits_source', ['orig', 'wear'])->nullable();
            $table->enum('result', ['PASS', 'FAIL'])->nullable();

            // finding
            $table->foreignId('codes_id')->nullable()->constrained('codes')->onDelete('set null');
            $table->text('finding_notes')->nullable();

            // repair decision
            $table->boolean('repair_required')->default(false);
            $table->enum('repair_action', ['replace', 'oversize', 'blend', 'machine', 'scrap', 'other'])->nullable();
            $table->foreignId('manual_repair_procedure_id')
                ->nullable()
                ->constrained('manual_repair_procedures')
                ->onDelete('set null');
            // computed: measured_hole + interference, rounded per oversize_rounding
            $table->decimal('calculated_oversize', 8, 4)->nullable();

            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('replaces_id')
                ->references('id')->on('wo_measurements')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('wo_measurements');
        Schema::enableForeignKeyConstraints();
    }
};
