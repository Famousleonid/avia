<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->unsignedSmallInteger('storage_rack')->nullable()->after('place');
            $table->unsignedSmallInteger('storage_level')->nullable()->after('storage_rack');
            $table->unsignedSmallInteger('storage_column')->nullable()->after('storage_level');

            $table->index(['storage_rack','storage_level','storage_column'], 'wo_storage_idx');
        });
    }

    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->dropIndex('wo_storage_idx');
            $table->dropColumn(['storage_rack','storage_level','storage_column']);
        });
    }
};
