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
        Schema::create('necessaries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        $csvFile = public_path('data/component_necessary.csv');
        $file = fopen($csvFile, 'r');
        $headers = fgetcsv($file);
        while (($row = fgetcsv($file)) !== false) {
            DB::table('necessaries')->insert([
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
        Schema::dropIfExists('necessaries');
    }
};
