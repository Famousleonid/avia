<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdrs', function (Blueprint $table) {
            // The highest wo_measurements.id the part's TDR processes were
            // built from (Update Processes / gate apply). The Update button
            // stays inactive until newer measurements appear.
            $table->unsignedBigInteger('last_synced_measurement_id')->nullable()->after('replaced_by_tdr_id');
        });
    }

    public function down(): void
    {
        Schema::table('tdrs', function (Blueprint $table) {
            $table->dropColumn('last_synced_measurement_id');
        });
    }
};
