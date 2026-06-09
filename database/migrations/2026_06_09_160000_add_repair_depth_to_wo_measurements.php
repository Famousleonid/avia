<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wo_measurements', function (Blueprint $table) {
            $table->decimal('repair_depth_a', 8, 4)->nullable()->after('repair_step_no');
            $table->decimal('repair_depth_b', 8, 4)->nullable()->after('repair_depth_a');
        });
    }

    public function down(): void
    {
        Schema::table('wo_measurements', function (Blueprint $table) {
            $table->dropColumn(['repair_depth_a', 'repair_depth_b']);
        });
    }
};
