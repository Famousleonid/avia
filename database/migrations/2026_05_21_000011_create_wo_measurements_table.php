<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wo_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workorder_id')->constrained()->onDelete('cascade');
            $table->foreignId('manual_parameter_id')
                ->nullable()
                ->constrained('manual_parameters')
                ->onDelete('restrict');
            $table->foreignId('manual_dimension_spec_id')
                ->nullable()
                ->constrained('manual_dimension_specs')
                ->onDelete('restrict');
            $table->enum('stage', ['initial', 'final'])->default('initial');
            $table->unsignedBigInteger('replaces_id')->nullable();

            $table->decimal('actual_value', 8, 4)->nullable();
            $table->enum('limits_source', ['orig', 'wear'])->nullable();
            $table->enum('result', ['PASS', 'FAIL'])->nullable();

            $table->foreignId('codes_id')->nullable()->constrained('codes')->onDelete('set null');
            $table->text('finding_notes')->nullable();

            $table->boolean('repair_required')->default(false);
            $table->enum('repair_action', ['repair', 'replace', 'oversize', 'blend', 'machine', 'scrap', 'other'])->nullable();
            $table->foreignId('manual_dimension_repair_rule_id')
                ->nullable()
                ->constrained('manual_dimension_repair_rules')
                ->onDelete('set null');
            $table->foreignId('manual_parameter_repair_rule_id')
                ->nullable()
                ->constrained('manual_parameter_repair_rules')
                ->onDelete('set null');
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
