<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wo_bushing_processes', function (Blueprint $table) {
            $table->string('repair_order')->nullable()->after('date_finish');
        });
    }

    public function down(): void
    {
        Schema::table('wo_bushing_processes', function (Blueprint $table) {
            $table->dropColumn('repair_order');
        });
    }
};
