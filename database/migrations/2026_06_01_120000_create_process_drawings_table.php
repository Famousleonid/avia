<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_drawings', function (Blueprint $table) {
            $table->id();
            // Drawing belongs to a specific process inside a POINT repair rule (Main).
            $table->foreignId('rule_process_id')
                  ->constrained('manual_parameter_rule_processes')
                  ->cascadeOnDelete();
            $table->string('drawing_type')->nullable(); // determines available placeholders (2b)
            $table->string('title')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('image_width')->nullable();
            $table->unsignedInteger('image_height')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_drawings');
    }
};
