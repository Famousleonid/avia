<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            if (! Schema::hasColumn('tdr_processes', 'standalone_ec_only')) {
                $table->boolean('standalone_ec_only')->default(false)->after('ec');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            if (Schema::hasColumn('tdr_processes', 'standalone_ec_only')) {
                $table->dropColumn('standalone_ec_only');
            }
        });
    }
};
