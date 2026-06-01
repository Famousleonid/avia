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
        Schema::create('master_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')
                  ->constrained('manuals')
                  ->cascadeOnDelete();
            $table->foreignId('inspection_component_id')
                  ->constrained('manual_inspection_components')
                  ->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->unique('inspection_component_id'); // one repair plan per part
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('master_rules');
    }
};
