<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workorders', function (Blueprint $table) {
            $table->id();
            $table->integer('number')->unique();
            $table->boolean('approve')->default(false);
            $table->timestamp('approve_at')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('manual')->nullable();
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('unit_id')->constrained();
            $table->foreignId('instruction_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('user_id')->constrained();
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
        Schema::dropIfExists('workorders');
    }
};
