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
            $table->foreignId('tdrs_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('processes_id')->nullable()->constrained()->onDelete('set null');
            $table->date('date_start')->nullable();
            $table->date('date_finish')->nullable();;
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
