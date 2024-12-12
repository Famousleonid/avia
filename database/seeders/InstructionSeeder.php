<?php

namespace Database\Seeders;


use App\Models\Unit;
use Illuminate\Database\Seeder;


class InstructionSeeder extends Seeder
{

    public function run()
    {


        $dataUnit = [
            ['partnumber' => '2309-3002-516', 'lib' => '145', 'description' => 'ERJ-145'],
            ['partnumber' => '49200-19', 'lib' => '158', 'description' => 'CRJ-900'],
            ['partnumber' => '2842A0000-026', 'lib' => '295', 'description' => 'ERJ-190'],
            ['partnumber' => '2900344-90', 'lib' => '73', 'description' => 'ATR-72'],
            ['partnumber' => 'A1800-12', 'lib' => '77', 'description' => 'A77'],
        ];
        Unit::insert($dataUnit);




    }
}
