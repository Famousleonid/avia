<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rm_reports', function (Blueprint $table): void {
            $table->foreignId('manual_service_bulletin_id')
                ->nullable()
                ->after('manual_id')
                ->constrained('manual_service_bulletins')
                ->nullOnDelete();
            $table->foreignId('source_assy_option_id')
                ->nullable()
                ->after('manual_service_bulletin_id')
                ->constrained('manual_part_group_options')
                ->nullOnDelete();
            $table->foreignId('target_assy_option_id')
                ->nullable()
                ->after('source_assy_option_id')
                ->constrained('manual_part_group_options')
                ->nullOnDelete();
        });

        Schema::table('workorders', function (Blueprint $table): void {
            $table->foreignId('modified_scope_part_group_option_id')
                ->nullable()
                ->after('scope_part_group_option_id')
                ->constrained('manual_part_group_options')
                ->nullOnDelete();
            $table->foreignId('modified_scope_rm_report_id')
                ->nullable()
                ->after('modified_scope_part_group_option_id')
                ->constrained('rm_reports')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('modified_scope_rm_report_id');
            $table->dropConstrainedForeignId('modified_scope_part_group_option_id');
        });

        Schema::table('rm_reports', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('target_assy_option_id');
            $table->dropConstrainedForeignId('source_assy_option_id');
            $table->dropConstrainedForeignId('manual_service_bulletin_id');
        });
    }
};
