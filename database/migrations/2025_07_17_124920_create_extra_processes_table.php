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
        Schema::create('extra_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workorder_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('component_id')->nullable()->constrained()->onDelete('set null');
            $table->json('processes')->nullable(); // JSON-поле для хранения массива процессов
            $table->unsignedInteger('qty')->default(1);

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
        Schema::dropIfExists('extra_processes');
    }
};
