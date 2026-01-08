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
        Schema::table('tdrs', function (Blueprint $table) {

            $table->dropColumn(['use_log_card', 'use_extra_forms']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tdrs', function (Blueprint $table) {
            $table->boolean('use_log_card')->default(false);
            $table->boolean('use_extra_forms')->default(false);
        });
    }
};


