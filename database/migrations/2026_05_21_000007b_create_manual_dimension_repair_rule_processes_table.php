<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_dimension_repair_rule_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_rule_id')
                ->constrained('manual_dimension_repair_rules')
                ->onDelete('cascade');
            $table->foreignId('manual_process_id')
                ->constrained('manual_processes')
                ->onDelete('cascade');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('manual_dimension_repair_rule_processes');
        Schema::enableForeignKeyConstraints();
    }
};
