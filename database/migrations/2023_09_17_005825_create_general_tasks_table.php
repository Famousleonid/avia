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
            $table->timestamps();
        });

        $dataGeneralTask = [
            ['name' => 'start'],
            ['name' => 'clean'],
            ['name' => 'disassembly'],
            ['name' => 'NDT List'],
            ['name' => 'CAD List'],
            ['name' => 'stress relief'],
            ['name' => 'check bushing'],
            ['name' => 'promote'],
            ['name' => 'assembly'],
            ['name' => 'paint'],
            ['name' => 'test'],
            ['name' => 'done'],
        ];

        GeneralTask::insert($dataGeneralTask);
    }


    public function down()
    {
        Schema::dropIfExists('general_tasks');
    }
};
