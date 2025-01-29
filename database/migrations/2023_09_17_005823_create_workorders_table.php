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
            $table->timestamp('approve_at')->nullable();
            $table->string('approve_name')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('description')->nullable();
            $table->string('amdt')->nullable();
            $table->string('place')->nullable();
            $table->timestamp('open_at')->nullable();
            $table->foreignId('unit_id')->constrained();
            $table->foreignId('instruction_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('user_id')->constrained();

            $table->boolean('external_damage')->default(false);
            $table->boolean('received_disassembly')->default(false);
            $table->boolean('nameplate_missing')->default(false);
            $table->boolean('preliminary_test_false')->default(false);
            $table->boolean('part_missing')->default(false);
            $table->boolean('new_parts')->default(false);

            $table->boolean('extra_parts')->default(false);
            $table->boolean('disassembly_upon_arrival')->default(false);


            $table->timestamps();
            $table->softDeletes();
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
