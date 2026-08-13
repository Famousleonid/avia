<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE process_names MODIFY form_number VARCHAR(255) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE process_names ALTER COLUMN form_number DROP NOT NULL');
        } elseif ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE process_names ALTER COLUMN form_number VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        DB::table('process_names')->whereNull('form_number')->update(['form_number' => '']);

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE process_names MODIFY form_number VARCHAR(255) NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE process_names ALTER COLUMN form_number SET NOT NULL');
        } elseif ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE process_names ALTER COLUMN form_number VARCHAR(255) NOT NULL');
        }
    }
};
