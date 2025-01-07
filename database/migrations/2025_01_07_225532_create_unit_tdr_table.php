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
        Schema::create('unit_tdr', function (Blueprint $table) {
            $table->id();

            $table->string('name');
        });


        $csvFile = public_path('data/unit_tdr.csv');
        $file = fopen($csvFile, 'r');
        $headers = fgetcsv($file);
        while (($row = fgetcsv($file)) !== false) {
            DB::table('unit_tdr')->insert([
                'name' => $row[0],
            ]);
        }
        fclose($file);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unit_tdr');
    }
};
