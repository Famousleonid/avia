<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            if (! Schema::hasColumn('tdr_processes', 'vendor_id')) {
                $table->foreignId('vendor_id')
                    ->nullable()
                    ->after('repair_order')
                    ->constrained('vendors')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            if (Schema::hasColumn('tdr_processes', 'vendor_id')) {
                $table->dropConstrainedForeignId('vendor_id');
            }
        });
    }
};
