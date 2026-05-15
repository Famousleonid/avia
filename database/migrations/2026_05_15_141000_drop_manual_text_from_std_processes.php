<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('std_processes', function (Blueprint $table): void {
            if (Schema::hasColumn('std_processes', 'manual')) {
                $table->dropColumn('manual');
            }
        });

        if (Schema::hasTable('workorder_std_process_items')) {
            \Illuminate\Support\Facades\DB::table('workorder_std_process_items')->delete();
        }
    }

    public function down(): void
    {
        Schema::table('std_processes', function (Blueprint $table): void {
            if (! Schema::hasColumn('std_processes', 'manual')) {
                $table->string('manual', 255)->nullable()->after('qty');
            }
        });
    }
};
