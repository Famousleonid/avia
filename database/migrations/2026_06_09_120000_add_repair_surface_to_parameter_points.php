<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_parameters', function (Blueprint $table) {
            $table->decimal('flange_clearance', 8, 4)->nullable()->after('repair_dim_max');
            $table->string('repair_surface_side', 4)->nullable()->after('flange_clearance'); // 'A','B','both'
            $table->decimal('max_repair_depth_a', 8, 4)->nullable()->after('repair_surface_side');
            $table->decimal('max_repair_depth_b', 8, 4)->nullable()->after('max_repair_depth_a');
        });
    }

    public function down(): void
    {
        Schema::table('manual_parameter_points', function (Blueprint $table) {
            if (Schema::hasColumn('manual_parameter_points', 'is_repair_surface')) {
                $table->dropColumn(['is_repair_surface', 'max_repair_depth']);
            }
        });

        Schema::table('manual_parameters', function (Blueprint $table) {
            $cols = array_filter(
                ['flange_clearance', 'repair_surface_side', 'max_repair_depth_a', 'max_repair_depth_b'],
                fn($c) => Schema::hasColumn('manual_parameters', $c)
            );
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};
