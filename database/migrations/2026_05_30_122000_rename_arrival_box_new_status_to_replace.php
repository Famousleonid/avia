<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('workorders')
            ->where('arrival_box_status', 'new')
            ->update(['arrival_box_status' => 'replace']);
    }

    public function down(): void
    {
        DB::table('workorders')
            ->where('arrival_box_status', 'replace')
            ->update(['arrival_box_status' => 'new']);
    }
};
