<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_drawing_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drawing_id')
                  ->constrained('process_drawings')
                  ->cascadeOnDelete();
            $table->string('element_type', 20); // 'dimension' | 'label' | 'text'

            // position (percent of image), x2/y2 for linear dimensions, label_x/y for the value box
            $table->decimal('x_pct', 6, 2)->nullable();
            $table->decimal('y_pct', 6, 2)->nullable();
            $table->decimal('x2_pct', 6, 2)->nullable();
            $table->decimal('y2_pct', 6, 2)->nullable();
            $table->decimal('label_x_pct', 6, 2)->nullable();
            $table->decimal('label_y_pct', 6, 2)->nullable();

            // dimension specifics
            $table->string('mask', 20)->nullable();          // 'diameter' | 'linear'
            $table->string('value_source', 20)->default('static'); // 'static' | 'measurement'
            $table->decimal('static_value', 12, 4)->nullable();
            // 2b: dynamic value from a measurement parameter (via F&C pairing)
            $table->foreignId('source_parameter_id')
                  ->nullable()
                  ->constrained('manual_parameters')
                  ->nullOnDelete();

            // label specifics
            $table->string('placeholder')->nullable(); // '{wo_number}', '{repair_number}'... (2b)
            $table->string('text')->nullable();         // free text / note

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_drawing_elements');
    }
};
