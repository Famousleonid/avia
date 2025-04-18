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


            $table->foreignId('workorder_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('component_id')->nullable()->constrained()->onDelete('set null');
            $table->string('serial_number')->nullable();
            $table->string('assy_serial_number')->nullable();

            $table->foreignId('codes_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('conditions_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('necessaries_id')->nullable()->constrained()->onDelete('set null');
            $table->string('description')->nullable();

            $table->unsignedInteger('qty')->default(1);

            $table->boolean('use_tdr')->default(false);
            $table->boolean('use_process_forms')->default(false);
            $table->boolean('use_log_card')->default(false);
            $table->boolean('use_extra_forms')->default(false);

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
        Schema::dropIfExists('tdrs');
    }
};
