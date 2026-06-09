<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_parameter_points', function (Blueprint $table) {
            $table->boolean('is_repair_surface')->default(false)->after('manual_dimension_point_id');
            $table->decimal('max_repair_depth', 8, 4)->nullable()->after('is_repair_surface');
        });

        Schema::table('manual_parameters', function (Blueprint $table) {
            $table->decimal('flange_clearance', 8, 4)->nullable()->after('interference_value');
        });
    }

    public function down(): void
    {
        Schema::table('manual_parameter_points', function (Blueprint $table) {
            $table->dropColumn(['is_repair_surface', 'max_repair_depth']);
        });

        Schema::table('manual_parameters', function (Blueprint $table) {
            $table->dropColumn('flange_clearance');
        });
    }
};
