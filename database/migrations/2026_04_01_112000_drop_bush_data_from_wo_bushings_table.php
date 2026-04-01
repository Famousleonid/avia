<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wo_bushings', function (Blueprint $table) {
            if (Schema::hasColumn('wo_bushings', 'bush_data')) {
                $table->dropColumn('bush_data');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wo_bushings', function (Blueprint $table) {
            if (! Schema::hasColumn('wo_bushings', 'bush_data')) {
                $table->json('bush_data')->nullable();
            }
        });
    }
};
