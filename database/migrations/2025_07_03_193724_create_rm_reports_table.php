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
        Schema::create('rm_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->nullable()->constrained()->onDelete('set null');
            $table->string('part_description');
            $table->string('mod_repair');
            $table->string('description');
            $table->string('ident_method')->nullable();
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
        Schema::dropIfExists('rm_reports');
    }
};
