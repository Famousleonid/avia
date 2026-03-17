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
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE codes MODIFY code VARCHAR(255) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE codes ALTER COLUMN code DROP NOT NULL');
        } else {
            DB::statement('ALTER TABLE codes ALTER COLUMN code VARCHAR(255) NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE codes MODIFY code VARCHAR(255) NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE codes ALTER COLUMN code SET NOT NULL');
        } else {
            DB::statement('ALTER TABLE codes ALTER COLUMN code VARCHAR(255) NOT NULL');
        }
    }
};
