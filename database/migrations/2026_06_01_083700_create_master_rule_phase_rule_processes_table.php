<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_rule_phase_rule_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_rule_id')
                  ->constrained('master_rule_phase_rules')
                  ->cascadeOnDelete();
            $table->foreignId('manual_process_id')
                  ->constrained('manual_processes')
                  ->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_rule_phase_rule_processes');
    }
};
