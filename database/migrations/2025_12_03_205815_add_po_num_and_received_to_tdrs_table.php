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
            $table->string('po_num')->nullable()->after('qty');
            $table->date('received')->nullable()->after('po_num');
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
            $table->dropColumn('po_num');
            $table->dropColumn('received');
        });
    }
};
