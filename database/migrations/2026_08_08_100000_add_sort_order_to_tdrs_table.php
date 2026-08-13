<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tdrs', 'sort_order')) {
            Schema::table('tdrs', function (Blueprint $table): void {
                $table->unsignedInteger('sort_order')->default(0)->after('qty');
                $table->index(['workorder_id', 'sort_order'], 'tdrs_workorder_sort_order_index');
            });
        }

        DB::table('tdrs')
            ->where('sort_order', 0)
            ->update(['sort_order' => DB::raw('id')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('tdrs', 'sort_order')) {
            Schema::table('tdrs', function (Blueprint $table): void {
                $table->dropIndex('tdrs_workorder_sort_order_index');
                $table->dropColumn('sort_order');
            });
        }
    }
};
