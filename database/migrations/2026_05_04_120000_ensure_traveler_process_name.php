<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $processNameId = DB::table('process_names')
            ->whereRaw('LOWER(TRIM(name)) = ?', ['traveler'])
            ->value('id');

        if (! $processNameId) {
            $insert = [
                'name' => 'Traveler',
                'process_sheet_name' => 'TRAVELER',
                'form_number' => 'TRV',
            ];

            if (Schema::hasColumn('process_names', 'print_form')) {
                $insert['print_form'] = false;
            }

            if (Schema::hasColumn('process_names', 'show_in_process_picker')) {
                $insert['show_in_process_picker'] = true;
            }

            if (Schema::hasColumn('process_names', 'std_days')) {
                $insert['std_days'] = null;
            }

            $processNameId = DB::table('process_names')->insertGetId($insert);
        }

        $processExists = DB::table('processes')
            ->where('process_names_id', $processNameId)
            ->whereRaw('LOWER(TRIM(process)) = ?', ['traveler'])
            ->exists();

        if (! $processExists) {
            DB::table('processes')->insert([
                'process_names_id' => $processNameId,
                'process' => 'Traveler',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Keep Traveler configuration and std_days intact if the migration is rolled back.
    }
};
