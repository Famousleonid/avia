<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table) {
            if (Schema::hasColumn('components', 'repair')) {
                $table->dropColumn('repair');
            }
        });

        Schema::table('components', function (Blueprint $table) {
            foreach (['kit', 'ndt_list', 'cad_list', 'stress_relief_list', 'paint_list'] as $column) {
                if (! Schema::hasColumn('components', $column)) {
                    $table->boolean($column)->default(false)->after('is_bush');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            foreach (['kit', 'ndt_list', 'cad_list', 'stress_relief_list', 'paint_list'] as $column) {
                if (Schema::hasColumn('components', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('components', function (Blueprint $table) {
            if (! Schema::hasColumn('components', 'repair')) {
                $table->boolean('repair')->default(false)->after('log_card');
            }
        });
    }
};
