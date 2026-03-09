<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('process_names', function (Blueprint $table) {
            $table->boolean('print_form')->default(false)->after('form_number');
        });
    }

    public function down()
    {
        Schema::table('process_names', function (Blueprint $table) {
            $table->dropColumn('print_form');
        });
    }
};
