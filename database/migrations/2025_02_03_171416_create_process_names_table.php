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
            $table->string('process_sheet_name');
            $table->string('form_number');
        });
        DB::table('process_names')->insert([
            ['name' => 'NDT-1','process_sheet_name'=>'NDT','form_number'=>'016'],
            ['name' => 'NDT-4','process_sheet_name'=>'NDT','form_number'=>'016'],
            ['name' => 'Bake (Stress relief)','process_sheet_name'=>'HEAT TREATMENT','form_number'=>'026'],
            ['name' => 'Cad stripping','process_sheet_name'=>'CADMIUM PLATING','form_number'=>'014'],
            ['name' => 'Chrome stripping','process_sheet_name'=>'CHROME PLATING','form_number'=>'015'],
            ['name' => 'HVOF stripping','process_sheet_name'=>'HVOF','form_number'=>'027'],
            ['name' => 'Silver stripping','process_sheet_name'=>'SILVER PLATING','form_number'=>'0__'],
            ['name' => 'E-Nickel stripping','process_sheet_name'=>'NICKEL PLATING','form_number'=>'019'],
            ['name' => 'S-Nickel stripping','process_sheet_name'=>'NICKEL PLATING','form_number'=>'019'],
            ['name' => 'Machining','process_sheet_name'=>'MACHINING','form_number'=>'018'],
            ['name' => 'Etch inspection','process_sheet_name'=>'ETCH INSPECTION','form_number'=>'013'],
            ['name' => 'Shot peening','process_sheet_name'=>'SHOT PEENING','form_number'=>'025'],
            ['name' => 'S. Nickel plating','process_sheet_name'=>'NICKEL PLATING','form_number'=>'019'],
            ['name' => 'E. Nickel plating','process_sheet_name'=>'NICKEL PLATING','form_number'=>'019'],
            ['name' => 'Chrome plate','process_sheet_name'=>'CHROME PLATING','form_number'=>'015'],
            ['name' => 'Passivation','process_sheet_name'=>'PASSIVATION','form_number'=>'029'],
            ['name' => 'Silver plate','process_sheet_name'=>'SILVER PLATING','form_number'=>'0__'],
            ['name' => 'HVOF plating','process_sheet_name'=>'HVOF','form_number'=>'027'],
            ['name' => 'Cad plate','process_sheet_name'=>'CADMIUM PLATING','form_number'=>'014'],
            ['name' => 'Eddy Current Test','process_sheet_name'=>'NDT','form_number'=>'016'],
            ['name' => 'BNI','process_sheet_name'=>'NDT','form_number'=>'016'],
            ['name' => 'Anodizing','process_sheet_name'=>'ANODIZING','form_number'=>'021'],
            ['name' => 'Xylan coating','process_sheet_name'=>'XYLAN COATING','form_number'=>'033'],
            ['name' => 'Repair ','process_sheet_name'=>'REPAIR APPLICATION','form_number'=>'017A'],
            ['name' => 'Paint ','process_sheet_name'=>'PAINT APPLICATION','form_number'=>'017'],
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
