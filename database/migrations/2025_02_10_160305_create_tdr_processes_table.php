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
        Schema::create('tdr_processes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('tdrs_id')->nullable()->constrained()->onDelete('set null'); // Внешний ключ для Tdr
            $table->foreignId('process_names_id')->nullable()->constrained('process_names')->onDelete('set null'); // Внешний ключ для ProcessName
            $table->json('processes')->nullable(); // JSON-поле для хранения массива процессов
            $table->date('date_start')->nullable(); // Дата начала
            $table->date('date_finish')->nullable(); // Дата завершения
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tdr_processes');
    }
};
