<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_dimension_repair_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_dimension_spec_id')
                ->constrained('manual_dimension_specs')
                ->onDelete('cascade');
            // NULL = rule applies to any defect code; set for code-specific rules (trigger=finding)
            $table->foreignId('codes_id')->nullable()->constrained('codes')->onDelete('cascade');
            $table->enum('trigger', ['below_orig', 'above_orig', 'below_wear', 'above_wear', 'finding', 'manual']);
            $table->enum('repair_action', ['repair', 'replace', 'oversize', 'blend', 'machine', 'scrap', 'other']);
            $table->boolean('no_repair')->default(false);
            $table->boolean('order_replacement')->default(false);
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
