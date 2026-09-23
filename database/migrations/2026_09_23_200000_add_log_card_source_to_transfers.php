<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->string('receipt_row_key', 100)->nullable();
            $table->foreignId('source_log_card_id')->nullable()->constrained('log_cards')->nullOnDelete();
            $table->string('source_log_row_key', 64)->nullable();
            $table->string('source_log_token', 64)->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->string('part_number')->nullable();
            $table->string('ipl_num')->nullable();
            $table->unique(['workorder_id', 'receipt_row_key'], 'transfer_receipt_unique');
            $table->index(['workorder_source', 'source_log_row_key'], 'transfer_source_log_index');
        });
    }
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropUnique('transfer_receipt_unique');
            $table->dropIndex('transfer_source_log_index');
            $table->dropConstrainedForeignId('source_log_card_id');
            $table->dropColumn(['receipt_row_key', 'source_log_row_key', 'source_log_token', 'qty', 'part_number', 'ipl_num']);
        });
    }
};
