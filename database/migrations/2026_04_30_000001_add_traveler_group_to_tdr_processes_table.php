<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table): void {
            if (! Schema::hasColumn('tdr_processes', 'traveler_group')) {
                $table->unsignedInteger('traveler_group')->nullable()->after('in_traveler');
            }
        });

        if (Schema::hasColumn('tdr_processes', 'traveler_group')) {
            DB::table('tdr_processes')
                ->where('in_traveler', true)
                ->whereNull('traveler_group')
                ->update(['traveler_group' => 1]);
        }
    }

    public function down(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table): void {
            if (Schema::hasColumn('tdr_processes', 'traveler_group')) {
                $table->dropColumn('traveler_group');
            }
        });
    }
};
