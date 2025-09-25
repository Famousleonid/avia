<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('general_task_id')->after('id');

            $table->foreign('general_task_id')
                ->references('id')
                ->on('general_tasks')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['general_task_id']);
            $table->dropColumn('general_task_id');
        });
    }
};
