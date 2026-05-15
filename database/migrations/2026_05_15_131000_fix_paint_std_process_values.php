<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('std_processes')
            ->where('std', 'paint')
            ->whereRaw('process REGEXP "^[0-9]+$"')
            ->orderBy('id')
            ->get(['id', 'process'])
            ->each(function ($row): void {
                $processText = DB::table('processes')
                    ->where('id', (int) $row->process)
                    ->value('process');

                if (is_string($processText) && trim($processText) !== '') {
                    DB::table('std_processes')
                        ->where('id', $row->id)
                        ->update(['process' => trim($processText)]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
