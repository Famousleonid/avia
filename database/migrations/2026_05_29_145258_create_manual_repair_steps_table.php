<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manual_repair_steps', function (Blueprint $table) {
            $table->id();
            // Repair steps belong to a single parameter (OD bushing or ID bore),
            // not to the point — Main Fitting and Bushing have independent ladders.
            $table->foreignId('manual_parameter_id')
                  ->constrained('manual_parameters')
                  ->cascadeOnDelete();
            $table->string('step_no', 20);          // e.g. 'R01', 'R02' (RS20)
            $table->foreignId('component_id')
                  ->nullable()
                  ->constrained('components')
                  ->nullOnDelete();                  // oversize bushing P/N
            // Values in inches (in), decimal:4
            $table->decimal('dim_min', 10, 4)->nullable();        // before cad plating (machining target)
            $table->decimal('dim_max', 10, 4)->nullable();
            $table->decimal('after_dim_min', 10, 4)->nullable();  // after cad plating (reference)
            $table->decimal('after_dim_max', 10, 4)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manual_repair_steps');
    }
};
