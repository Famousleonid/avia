<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('std_processes', function (Blueprint $table) {
            $table->string('eff_code', 255)->nullable()->after('manual');
        });
    }

    public function down(): void
    {
        Schema::table('std_processes', function (Blueprint $table) {
            $table->dropColumn('eff_code');
        });
    }
};
