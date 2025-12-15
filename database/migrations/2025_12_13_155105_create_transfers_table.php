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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workorder_id')->constrained('workorders')->onDelete('cascade');
            $table->foreignId('workorder_source')->nullable()->constrained('workorders')->onDelete('set null');
            $table->foreignId('component_id')->constrained('components')->onDelete('cascade');
            $table->string('component_sn')->nullable();
            $table->foreignId('reason')->nullable()->constrained('codes')->onDelete('set null');
            $table->string('unit_on_po')->nullable();
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
        Schema::dropIfExists('transfers');
    }
};
