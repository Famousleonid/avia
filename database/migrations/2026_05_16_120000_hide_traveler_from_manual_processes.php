<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $travelerProcessNameIds = DB::table('process_names')
            ->whereRaw('LOWER(TRIM(name)) = ?', ['traveler'])
            ->pluck('id');

        if ($travelerProcessNameIds->isEmpty()) {
            return;
        }

        if (Schema::hasColumn('process_names', 'show_in_process_picker')) {
            DB::table('process_names')
                ->whereIn('id', $travelerProcessNameIds)
                ->update(['show_in_process_picker' => false]);
        }

        $travelerProcessIds = DB::table('processes')
            ->whereIn('process_names_id', $travelerProcessNameIds)
            ->pluck('id');

        if ($travelerProcessIds->isNotEmpty()) {
            DB::table('manual_processes')
                ->whereIn('processes_id', $travelerProcessIds)
                ->delete();
        }
    }

    public function down(): void
    {
        // Traveler is a system process name; do not make it selectable again on rollback.
    }
};
