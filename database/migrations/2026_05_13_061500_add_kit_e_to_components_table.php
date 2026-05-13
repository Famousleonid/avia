<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table) {
            if (! Schema::hasColumn('components', 'kit_e')) {
                $table->boolean('kit_e')->default(false)->after('kit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            if (Schema::hasColumn('components', 'kit_e')) {
                $table->dropColumn('kit_e');
            }
        });
    }
};
