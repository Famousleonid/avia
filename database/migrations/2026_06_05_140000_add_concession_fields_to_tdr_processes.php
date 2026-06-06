<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EC-Finish: OEM concession granted for an EC process — recorded per-WO on the EC row.
 *   concession_number — OEM approval / concession number
 *   concession_date   — date the concession was granted
 *   concession_oem    — granting authority (OEM)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            $table->string('concession_number', 100)->nullable()->after('standalone_ec_only');
            $table->date('concession_date')->nullable()->after('concession_number');
            $table->string('concession_oem', 100)->nullable()->after('concession_date');
        });
    }

    public function down(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            $table->dropColumn(['concession_number', 'concession_date', 'concession_oem']);
        });
    }
};
