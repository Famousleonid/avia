<?php

use App\Models\GeneralTask;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::create('general_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');

        });

        $dataGeneralTask = [
            ['name' => 'Start'],
            ['name' => 'Disassembly'],
            ['name' => 'Standard processes'],
            ['name' => 'Assembly'],
            ['name' => 'Final Test'],
            ['name' => 'Done.'],

        ];

        GeneralTask::insert($dataGeneralTask);
    }


    public function down()
    {
        Schema::dropIfExists('general_tasks');
    }
};
