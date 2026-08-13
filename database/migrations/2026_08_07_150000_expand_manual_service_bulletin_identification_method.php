<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->resizeIdentificationMethod(600);
    }

    public function down(): void
    {
        $this->resizeIdentificationMethod(255);
    }

    private function resizeIdentificationMethod(int $length): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE manual_service_bulletins MODIFY identification_method VARCHAR({$length}) NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE manual_service_bulletins ALTER COLUMN identification_method TYPE VARCHAR({$length})");
        } elseif ($driver === 'sqlsrv') {
            DB::statement("ALTER TABLE manual_service_bulletins ALTER COLUMN identification_method VARCHAR({$length}) NULL");
        }
    }
};
