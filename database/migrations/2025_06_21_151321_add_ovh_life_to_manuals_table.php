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
        Schema::table('manuals', function (Blueprint $table) {
            $table->string('ovh_life')->nullable()->after('scopes_id');
            $table->string('reg_sb')->nullable()->after('ovh_life');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('manuals', function (Blueprint $table) {
            $table->dropColumn('ovh_life');
            $table->dropColumn('reg_sb');
        });
    }
};
