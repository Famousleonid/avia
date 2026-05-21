<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {


    public function up()
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('material')->nullable();
            $table->string('specification')->nullable();
            $table->string('ver')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
            $table->index('code');
            $table->index('specification');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('materials');
    }
};
