<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            if (!Schema::hasColumn('tdr_processes', 'in_traveler')) {
                $table->boolean('in_traveler')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            if (Schema::hasColumn('tdr_processes', 'in_traveler')) {
                $table->dropColumn('in_traveler');
            }
        });
    }
};
