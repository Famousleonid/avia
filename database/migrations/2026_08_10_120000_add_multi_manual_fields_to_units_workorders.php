<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->json('additional_manual_ids')->nullable()->after('manual_id');
        });

        Schema::table('workorders', function (Blueprint $table): void {
            $table->json('additional_manual_ids')->nullable()->after('unit_id');
            $table->json('not_used_manual_ids')->nullable()->after('additional_manual_ids');
        });

        Schema::table('workorder_std_process_items', function (Blueprint $table): void {
            $table->foreignId('manual_id')
                ->nullable()
                ->after('workorder_id')
                ->constrained('manuals')
                ->nullOnDelete();
            $table->index(['workorder_id', 'manual_id'], 'wo_std_items_workorder_manual_index');
        });
    }

    public function down(): void
    {
        Schema::table('workorder_std_process_items', function (Blueprint $table): void {
            $table->dropIndex('wo_std_items_workorder_manual_index');
            $table->dropConstrainedForeignId('manual_id');
        });

        Schema::table('workorders', function (Blueprint $table): void {
            $table->dropColumn(['additional_manual_ids', 'not_used_manual_ids']);
        });

        Schema::table('units', function (Blueprint $table): void {
            $table->dropColumn('additional_manual_ids');
        });
    }
};
