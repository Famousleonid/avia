<?php

use App\Models\Task;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $dataTask = [
            ['name' => 'machining'],
            ['name' => 'NDT'],
            ['name' => 'CAD'],
            ['name' => 'rechrome'],
            ['name' => 'shot peen'],
            ['name' => 'anodaizing'],
            ['name' => 'nickel'],
            ['name' => 'stress relief'],
            ['name' => 'paint'],
        ];

        Task::insert($dataTask);
    }


    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};
