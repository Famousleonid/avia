<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->boolean('is_draft')
                ->default(false)
                ->after('number')
                ->index();
        });
    }


    public function down()
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->dropIndex(['is_draft']);
            $table->dropColumn('is_draft');
        });
    }
};
