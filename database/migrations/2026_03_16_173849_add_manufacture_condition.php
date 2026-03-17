<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!DB::table('conditions')->where('name', 'Manufacture')->exists()) {
            DB::table('conditions')->insert([
                'name' => 'Manufacture',
                'unit' => false,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('conditions')->where('name', 'Manufacture')->delete();
    }
};
