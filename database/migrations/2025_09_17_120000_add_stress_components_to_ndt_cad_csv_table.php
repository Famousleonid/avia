<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ndt_cad_csv', function (Blueprint $table) {
            $table->json('stress_components')->nullable()->after('cad_components');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ndt_cad_csv', function (Blueprint $table) {
            $table->dropColumn('stress_components');
        });
    }
};
