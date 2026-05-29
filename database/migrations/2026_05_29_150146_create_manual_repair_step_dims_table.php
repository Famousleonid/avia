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
        Schema::create('manual_repair_step_dims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_step_id')
                  ->constrained('manual_repair_steps')
                  ->cascadeOnDelete();
            $table->foreignId('manual_parameter_id')
                  ->constrained('manual_parameters')
                  ->cascadeOnDelete();
            // Values in inches (in), decimal:4 — before cadmium plating (machining target)
            $table->decimal('dim_min', 10, 4)->nullable();
            $table->decimal('dim_max', 10, 4)->nullable();
            // After cadmium plating (inspection reference, optional)
            $table->decimal('after_dim_min', 10, 4)->nullable();
            $table->decimal('after_dim_max', 10, 4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manual_repair_step_dims');
    }
};
