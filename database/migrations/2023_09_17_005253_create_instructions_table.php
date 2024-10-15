<?php

use App\Models\Instruction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::create('instructions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        $dataInstruction = [
            ['name' => 'Overhaul'],
            ['name' => 'Repair'],
            ['name' => 'Test & inspect'],
            ['name' => '60M Iinspection'],
            ['name' => '96M Iinspection'],
        ];

        Instruction::insert($dataInstruction);
    }


    public function down()
    {
        Schema::dropIfExists('instructions');
    }
};
