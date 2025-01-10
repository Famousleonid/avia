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
        Schema::create('tdrs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('workorder_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('component_id')->nullable()->constrained()->onDelete('set null');
            $table->string('serial_number')->nullable();

            $table->foreignId('codes_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('conditions_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('necessaries_id')->nullable()->constrained()->onDelete('set null');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tdrs');
    }
};
