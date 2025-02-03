<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('process_names', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        DB::table('process_names')->insert([
            ['name' => 'NDT-1'],
            ['name' => 'NDT-4'],
            ['name' => 'Bake (Stress relief)'],
            ['name' => 'Cad stripping'],
            ['name' => 'Chrome stripping'],
            ['name' => 'HVOF stripping'],
            ['name' => 'Silver stripping'],
            ['name' => 'E-Nickel stripping'],
            ['name' => 'S-Nickel stripping'],
            ['name' => 'Machining'],
            ['name' => 'Etch inspection'],
            ['name' => 'Shot peening'],
            ['name' => 'S. Nickel plating'],
            ['name' => 'E. Nickel plating'],
            ['name' => 'Chrome plate'],
            ['name' => 'Passivation'],
            ['name' => 'Silver plate'],
            ['name' => 'HVOF plating'],
            ['name' => 'Silver plating'],
            ['name' => 'Cad plate'],
            ['name' => 'Eddy Current Test'],
            ['name' => 'BNI'],
            ['name' => 'Anodizing'],
            ['name' => 'Xylan coating'],
            ['name' => 'Paint '],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('process_names');
    }
};
