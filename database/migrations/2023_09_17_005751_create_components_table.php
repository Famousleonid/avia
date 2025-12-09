<?php

use App\Models\Component;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('purt_number')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        $dataComponent = [
            ['name' => 'Pin'],
            ['name' => 'Bracket'],
            ['name' => 'Axle'],
            ['name' => 'Main Fitting'],
            ['name' => 'Pintle Pin'],
        ];
        Component::insert($dataComponent);
    }

    public function down()
    {
        Schema::dropIfExists('components');
    }
};
