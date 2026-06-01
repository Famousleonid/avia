<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_document_elements', function (Blueprint $table) {
            $table->id();
            // Elements (dimensions / labels) live on a PAGE — available on any page.
            $table->foreignId('page_id')
                  ->constrained('process_document_pages')
                  ->cascadeOnDelete();
            $table->string('element_type', 20); // 'dimension' | 'label' | 'text'

            $table->decimal('x_pct', 6, 2)->nullable();
            $table->decimal('y_pct', 6, 2)->nullable();
            $table->decimal('x2_pct', 6, 2)->nullable();
            $table->decimal('y2_pct', 6, 2)->nullable();
            $table->decimal('label_x_pct', 6, 2)->nullable();
            $table->decimal('label_y_pct', 6, 2)->nullable();

            // dimension specifics
            $table->string('mask', 20)->nullable();                 // 'diameter' | 'linear'
            $table->string('value_source', 20)->default('static');  // 'static' | 'measurement'
            $table->decimal('static_value', 12, 4)->nullable();
            $table->foreignId('source_parameter_id')
                  ->nullable()
                  ->constrained('manual_parameters')
                  ->nullOnDelete();

            // label specifics
            $table->string('placeholder')->nullable(); // '{wo_number}', '{repair_number}'...
            $table->string('text')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_document_elements');
    }
};
