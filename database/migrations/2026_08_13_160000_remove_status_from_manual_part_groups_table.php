<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('manual_part_groups')
            ->whereIn('status', ['draft', 'inactive'])
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        Schema::table('manual_part_groups', function (Blueprint $table): void {
            $table->index(['manual_id', 'type'], 'manual_part_groups_manual_type_idx');
        });
        Schema::table('manual_part_groups', function (Blueprint $table): void {
            $table->dropIndex('manual_part_groups_lookup_idx');
        });
        Schema::table('manual_part_groups', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('manual_part_groups', function (Blueprint $table): void {
            $table->string('status', 16)->default('active')->after('type');
        });
        Schema::table('manual_part_groups', function (Blueprint $table): void {
            $table->index(['manual_id', 'status', 'type'], 'manual_part_groups_lookup_idx');
        });
        Schema::table('manual_part_groups', function (Blueprint $table): void {
            $table->dropIndex('manual_part_groups_manual_type_idx');
        });
    }
};
