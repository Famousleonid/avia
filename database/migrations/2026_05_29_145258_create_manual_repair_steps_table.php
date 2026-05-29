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
            $table->foreignId('dimension_point_id')
                  ->constrained('manual_dimension_points')
                  ->cascadeOnDelete();
            $table->string('step_no', 20);          // e.g. 'R01', 'R02' (RS20)
            $table->foreignId('component_id')
                  ->nullable()
                  ->constrained('components')
                  ->nullOnDelete();                  // oversize bushing P/N
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
