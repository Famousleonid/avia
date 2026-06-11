<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('manual_parameters', function (Blueprint $table) {
            $table->decimal('repair_dim_min', 8, 4)->nullable()->after('wear_dim_max');
            $table->decimal('repair_dim_max', 8, 4)->nullable()->after('repair_dim_min');
        });
    }

    public function down()
    {
        Schema::table('manual_parameters', function (Blueprint $table) {
            $table->dropColumn(['repair_dim_min', 'repair_dim_max']);
        });
    }
};
