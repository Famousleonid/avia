<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {


    public function up()
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('material')->nullable();
            $table->string('specification')->nullable();
            $table->string('ver')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
            $table->index('code');
        });

        $csvFile = public_path('data/materials.csv');
        $file = fopen($csvFile, 'r');
        $headers = fgetcsv($file);
        while (($row = fgetcsv($file)) !== false) {
            DB::table('materials')->insert([
                'code' => $row[1],
                'material' => $row[2],
                'specification' => $row[3],
                'ver' => $row[4],
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
        Schema::dropIfExists('materials');
    }
};
