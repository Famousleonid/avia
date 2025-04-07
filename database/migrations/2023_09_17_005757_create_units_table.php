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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique();
            $table->boolean('verified')->default(false);
            $table->foreignId('manual_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

        });

//        $csvFile = public_path('data/units.csv');
//        $file = fopen($csvFile, 'r');
//        $headers = fgetcsv($file);
//        while (($row = fgetcsv($file)) !== false) {
//            DB::table('units')->insert([
//                'part_number' => $row[1],
//            ]);
//        }
//        fclose($file);
        $csvFile = public_path('data/unit_n.csv');
        $file = fopen($csvFile, 'r');
        $headers = fgetcsv($file);
        while (($row = fgetcsv($file)) !== false) {
            DB::table('units')->insert([
                'part_number' => $row[2],
                'verified' => $row[3],
                'manual_id' => $row[4],
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
        Schema::dropIfExists('units');
    }
};
