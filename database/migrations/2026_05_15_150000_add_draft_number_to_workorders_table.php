<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('workorders', 'draft_number')) {
            Schema::table('workorders', function (Blueprint $table) {
                $table->unsignedInteger('draft_number')->nullable()->after('number');
                $table->unique('draft_number', 'workorders_draft_number_unique');
            });
        }

        DB::table('workorders')
            ->whereNull('draft_number')
            ->whereBetween('number', [1, 99999])
            ->update(['draft_number' => DB::raw('number')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('workorders', 'draft_number')) {
            Schema::table('workorders', function (Blueprint $table) {
                $table->dropUnique('workorders_draft_number_unique');
                $table->dropColumn('draft_number');
            });
        }
    }
};
