<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            if (!Schema::hasColumn('tdr_processes', 'ignore_row')) {
                $table->boolean('ignore_row')->default(false)->after('date_finish');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            if (Schema::hasColumn('tdr_processes', 'ignore_row')) {
                $table->dropColumn('ignore_row');
            }
        });
    }
};

