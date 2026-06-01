<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quantum_ro_lines', function (Blueprint $table): void {
            $table->string('apply_status', 40)->nullable()->after('last_seen_at')->index();
            $table->text('apply_message')->nullable()->after('apply_status');
            $table->string('applied_target_table', 80)->nullable()->after('apply_message');
            $table->unsignedBigInteger('applied_target_id')->nullable()->after('applied_target_table');
            $table->string('applied_source_hash', 64)->nullable()->after('applied_target_id');
            $table->timestamp('applied_at')->nullable()->after('applied_source_hash');

            $table->index(['applied_target_table', 'applied_target_id'], 'quantum_ro_lines_applied_target_idx');
            $table->index('applied_source_hash');
        });
    }

    public function down(): void
    {
        Schema::table('quantum_ro_lines', function (Blueprint $table): void {
            $table->dropIndex(['apply_status']);
            $table->dropIndex('quantum_ro_lines_applied_target_idx');
            $table->dropIndex(['applied_source_hash']);
            $table->dropColumn([
                'apply_status',
                'apply_message',
                'applied_target_table',
                'applied_target_id',
                'applied_source_hash',
                'applied_at',
            ]);
        });
    }
};
