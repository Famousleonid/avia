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
        Schema::table('tdr_processes', function (Blueprint $table) {
            $table->boolean('ec')->default(false)->nullable()->after('processes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            $table->dropColumn('ec');
        });
    }
};
