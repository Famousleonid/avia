<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('manual_part_group_coverages', function (Blueprint $table): void {
            $table->string('choice_slot', 80)->nullable()->after('expand_ipl_family');
            $table->index(['manual_part_group_option_id', 'choice_slot'], 'mpg_coverage_choice_slot_idx');
        });

        Schema::create('workorder_assy_configuration_choices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workorder_id');
            $table->foreignId('parent_option_id');
            $table->string('choice_slot', 80);
            $table->foreignId('selected_coverage_id');
            $table->foreignId('selected_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('workorder_id', 'wo_assy_choice_wo_fk')->references('id')->on('workorders')->cascadeOnDelete();
            $table->foreign('parent_option_id', 'wo_assy_choice_parent_fk')->references('id')->on('manual_part_group_options')->cascadeOnDelete();
            $table->foreign('selected_coverage_id', 'wo_assy_choice_coverage_fk')->references('id')->on('manual_part_group_coverages')->cascadeOnDelete();
            $table->foreign('selected_by_user_id', 'wo_assy_choice_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['workorder_id', 'parent_option_id', 'choice_slot'], 'wo_assy_choice_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workorder_assy_configuration_choices');
        Schema::table('manual_part_group_coverages', function (Blueprint $table): void {
            $table->dropIndex('mpg_coverage_choice_slot_idx');
            $table->dropColumn('choice_slot');
        });
    }
};
