<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');

        });
        DB::table('vendors')->insert([
            ['name' => 'Aviatechnik'],
            ['name' => 'Micro Custom'],
            ['name' => 'Ampere Metal Finishing'],
            ['name' => 'Airco Plating'],
            ['name' => 'Ontario Chrome'],
            ['name' => 'Skyservice'],
            ['name' => 'Electroless Nickel Tech. (EN Tech.)'],
            ['name' => 'Southwest United Canada'],
            ['name' => 'AeroTek Manufacturing Ltd.'],
            ['name' => 'Advanced Heat Treatment'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()

    {
        Schema::dropIfExists('vendors');
    }
};
