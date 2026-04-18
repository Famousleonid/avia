<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wo_bushing_processes', function (Blueprint $table) {
            if (! Schema::hasColumn('wo_bushing_processes', 'vendor_id')) {
                $table->foreignId('vendor_id')
                    ->nullable()
                    ->after('repair_order')
                    ->constrained('vendors')
                    ->nullOnDelete();
            }
        });

        Schema::table('wo_bushing_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('wo_bushing_batches', 'vendor_id')) {
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
        Schema::table('wo_bushing_batches', function (Blueprint $table) {
            if (Schema::hasColumn('wo_bushing_batches', 'vendor_id')) {
                $table->dropConstrainedForeignId('vendor_id');
            }
        });

        Schema::table('wo_bushing_processes', function (Blueprint $table) {
            if (Schema::hasColumn('wo_bushing_processes', 'vendor_id')) {
                $table->dropConstrainedForeignId('vendor_id');
            }
        });
    }
};
