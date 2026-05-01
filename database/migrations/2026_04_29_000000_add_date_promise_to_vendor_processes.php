<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['tdr_processes', 'wo_bushing_processes', 'wo_bushing_batches'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'date_promise')) {
                    $table->date('date_promise')->nullable()->after('date_finish');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['wo_bushing_batches', 'wo_bushing_processes', 'tdr_processes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'date_promise')) {
                    $table->dropColumn('date_promise');
                }
            });
        }
    }
};
