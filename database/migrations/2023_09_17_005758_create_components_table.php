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
            $table->string('part_number');
            $table->string('assy_part_number')->nullable();
            $table->string('name');
            $table->string('ipl_num');
            $table->string('assy_ipl_num')->nullable();
            $table->boolean('log_card')->default(false);

            $table->foreignId('manual_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

//        $dataComponent = [
//            ['name' => 'Pin'],
//            ['name' => 'Bracket'],
//            ['name' => 'Axle'],
//            ['name' => 'Main Fitting'],
//            ['name' => 'Pintle Pin'],
//        ];
//        Component::insert($dataComponent);
    }

    public function down()
    {
        Schema::dropIfExists('components');
    }
};
