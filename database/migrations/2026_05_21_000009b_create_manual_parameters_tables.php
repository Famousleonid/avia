<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->constrained('manuals')->onDelete('cascade');
            $table->foreignId('inspection_component_id')->nullable()
                ->constrained('manual_inspection_components')->onDelete('set null');
            $table->string('description');
            $table->boolean('is_required')->default(true);
            $table->decimal('orig_dim_min', 8, 4)->nullable();
            $table->decimal('orig_dim_max', 8, 4)->nullable();
            $table->decimal('wear_dim_min', 8, 4)->nullable();
            $table->decimal('wear_dim_max', 8, 4)->nullable();
            $table->text('inspection')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('manual_parameter_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_parameter_id')
                ->constrained('manual_parameters')->onDelete('cascade');
            $table->foreignId('codes_id')->constrained('codes')->onDelete('cascade');
            $table->unique(['manual_parameter_id', 'codes_id'], 'param_code_unique');
        });

        Schema::create('manual_parameter_repair_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_parameter_id')
                ->constrained('manual_parameters')->onDelete('cascade');
            $table->string('name', 100)->nullable();
            $table->boolean('order_replacement')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('manual_parameter_rule_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_rule_id')
                ->constrained('manual_parameter_repair_rules')->onDelete('cascade');
            $table->enum('trigger', ['below_orig', 'above_orig', 'below_wear', 'above_wear', 'finding', 'manual']);
            $table->foreignId('codes_id')->nullable()->constrained('codes')->onDelete('set null');
        });

        Schema::create('manual_parameter_rule_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_rule_id')
                ->constrained('manual_parameter_repair_rules')->onDelete('cascade');
            $table->foreignId('manual_process_id')
                ->constrained('manual_processes')->onDelete('cascade');
            $table->unsignedTinyInteger('sort_order')->default(0);
        });

        Schema::create('manual_parameter_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_parameter_id')
                ->constrained('manual_parameters')->onDelete('cascade');
            $table->foreignId('manual_dimension_point_id')
                ->constrained('manual_dimension_points')->onDelete('cascade');
            $table->unique(['manual_parameter_id', 'manual_dimension_point_id'], 'param_point_unique');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('manual_parameter_points');
        Schema::dropIfExists('manual_parameter_rule_processes');
        Schema::dropIfExists('manual_parameter_rule_triggers');
        Schema::dropIfExists('manual_parameter_repair_rules');
        Schema::dropIfExists('manual_parameter_codes');
        Schema::dropIfExists('manual_parameters');
        Schema::enableForeignKeyConstraints();
    }
};
