<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_part_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('manual_id')->constrained('manuals')->cascadeOnDelete();
            $table->foreignId('manual_service_bulletin_id')->nullable()->constrained('manual_service_bulletins')->nullOnDelete();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('behavior', 24);
            $table->string('type', 32);
            $table->string('status', 16)->default('draft');
            $table->json('applies_to')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['manual_id', 'status', 'type'], 'manual_part_groups_lookup_idx');
        });

        Schema::create('manual_part_group_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('manual_part_group_id')->constrained('manual_part_groups')->cascadeOnDelete();
            $table->foreignId('component_id')->nullable()->constrained('components')->nullOnDelete();
            $table->string('part_number', 100);
            $table->string('ipl_num', 50)->nullable();
            $table->string('label')->nullable();
            $table->string('option_kind', 24)->default('standard');
            $table->decimal('oversize_value', 8, 4)->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['manual_part_group_id', 'sort_order'], 'manual_part_group_options_sort_idx');
        });

        Schema::create('manual_part_group_coverages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('manual_part_group_option_id')->constrained('manual_part_group_options')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('components')->cascadeOnDelete();
            $table->foreignId('legacy_component_assembly_id')->nullable()->constrained('component_assemblies')->nullOnDelete();
            $table->unsignedInteger('qty')->default(1);
            $table->json('applies_to')->nullable();
            $table->timestamps();

            $table->unique(
                ['manual_part_group_option_id', 'component_id'],
                'manual_part_group_coverages_option_component_unique'
            );
            $table->unique('legacy_component_assembly_id', 'manual_part_group_coverages_legacy_unique');
        });

        Schema::create('workorder_part_group_selections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workorder_id')->constrained('workorders')->cascadeOnDelete();
            $table->unsignedBigInteger('manual_part_group_id');
            $table->unsignedBigInteger('manual_part_group_option_id');
            $table->unsignedInteger('qty')->default(1);
            $table->unsignedBigInteger('selected_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('manual_part_group_id', 'wo_part_group_selection_group_fk')
                ->references('id')->on('manual_part_groups')->cascadeOnDelete();
            $table->foreign('manual_part_group_option_id', 'wo_part_group_selection_option_fk')
                ->references('id')->on('manual_part_group_options')->cascadeOnDelete();
            $table->foreign('selected_by_user_id', 'wo_part_group_selection_user_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->unique(
                ['workorder_id', 'manual_part_group_id'],
                'workorder_part_group_selections_workorder_group_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workorder_part_group_selections');
        Schema::dropIfExists('manual_part_group_coverages');
        Schema::dropIfExists('manual_part_group_options');
        Schema::dropIfExists('manual_part_groups');
    }
};
