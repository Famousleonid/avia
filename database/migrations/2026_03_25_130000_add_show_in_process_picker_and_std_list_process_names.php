<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_names', function (Blueprint $table) {
            $table->boolean('show_in_process_picker')->default(true)->after('print_form');
        });

        $rows = [
            [
                'name' => 'STD NDT List',
                'process_sheet_name' => 'STD LIST',
                'form_number' => 'STD',
                'print_form' => false,
                'show_in_process_picker' => false,
            ],
            [
                'name' => 'STD CAD List',
                'process_sheet_name' => 'STD LIST',
                'form_number' => 'STD',
                'print_form' => false,
                'show_in_process_picker' => false,
            ],
            [
                'name' => 'STD Stress relief List',
                'process_sheet_name' => 'STD LIST',
                'form_number' => 'STD',
                'print_form' => false,
                'show_in_process_picker' => false,
            ],
            [
                'name' => 'STD Paint List',
                'process_sheet_name' => 'STD LIST',
                'form_number' => 'STD',
                'print_form' => false,
                'show_in_process_picker' => false,
            ],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('process_names')->where('name', $row['name'])->exists();
            if (!$exists) {
                DB::table('process_names')->insert($row);
            }
        }
    }

    public function down(): void
    {
        DB::table('process_names')->whereIn('name', [
            'STD NDT List',
            'STD CAD List',
            'STD Stress relief List',
            'STD Paint List',
        ])->delete();

        Schema::table('process_names', function (Blueprint $table) {
            $table->dropColumn('show_in_process_picker');
        });
    }
};
