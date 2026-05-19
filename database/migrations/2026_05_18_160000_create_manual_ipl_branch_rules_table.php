<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_ipl_branch_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->string('unit_match_value', 64)->nullable();
            $table->string('include_prefix', 32);
            $table->string('exclude_prefix', 32);
            $table->timestamps();

            $table->unique(['manual_id', 'unit_match_value'], 'manual_ipl_branch_rules_manual_match_unique');
            $table->index(['manual_id', 'is_default'], 'manual_ipl_branch_rules_manual_default_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_ipl_branch_rules');
    }
};
