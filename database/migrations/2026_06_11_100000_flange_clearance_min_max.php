<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_parameters', function (Blueprint $table) {
            $table->renameColumn('flange_clearance', 'flange_clearance_min');
        });
        Schema::table('manual_parameters', function (Blueprint $table) {
            $table->decimal('flange_clearance_max', 8, 4)->nullable()->after('flange_clearance_min');
        });
    }

    public function down(): void
    {
        Schema::table('manual_parameters', function (Blueprint $table) {
            $table->dropColumn('flange_clearance_max');
        });
        Schema::table('manual_parameters', function (Blueprint $table) {
            $table->renameColumn('flange_clearance_min', 'flange_clearance');
        });
    }
};
