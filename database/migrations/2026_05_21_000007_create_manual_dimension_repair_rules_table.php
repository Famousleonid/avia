<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Defines what repair action to take based on trigger condition.
        // trigger=fail: out-of-tolerance measurement
        // trigger=finding: specific damage code found (codes_id required)
        // trigger=manual: technician selects manually
        Schema::create('manual_dimension_repair_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_dimension_spec_id')
                ->constrained('manual_dimension_specs')
                ->onDelete('cascade');
            // NULL = rule applies to any code; set for code-specific rules
            $table->foreignId('codes_id')->nullable()->constrained('codes')->onDelete('cascade');
            $table->enum('trigger', ['fail', 'finding', 'manual']);
            $table->enum('repair_action', ['replace', 'oversize', 'blend', 'machine', 'scrap', 'other']);
            $table->foreignId('manual_repair_procedure_id')
                ->nullable()
                ->constrained('manual_repair_procedures')
                ->onDelete('set null');
            $table->boolean('no_repair')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('manual_dimension_repair_rules');
        Schema::enableForeignKeyConstraints();
    }
};
