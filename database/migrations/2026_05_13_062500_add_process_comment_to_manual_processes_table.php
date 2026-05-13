<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_processes', function (Blueprint $table) {
            if (! Schema::hasColumn('manual_processes', 'process_comment')) {
                $table->text('process_comment')->nullable()->after('processes_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('manual_processes', function (Blueprint $table) {
            if (Schema::hasColumn('manual_processes', 'process_comment')) {
                $table->dropColumn('process_comment');
            }
        });
    }
};
