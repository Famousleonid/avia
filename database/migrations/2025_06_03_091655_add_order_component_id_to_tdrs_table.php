<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run tcomponent_idhe migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tdrs', function (Blueprint $table) {
            $table->unsignedBigInteger('order_component_id')->nullable()->after('id');
        });
    }

    public function down()
    {
        Schema::table('tdrs', function (Blueprint $table) {
            // Если был внешний ключ:
            // $table->dropForeign(['order_component_id']);
            $table->dropColumn('order_component_id');
        });
    }
};
