<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workorders', function (Blueprint $table): void {
            if (! Schema::hasColumn('workorders', 'arrival_box_status')) {
                $table->string('arrival_box_status', 16)->nullable()->after('storage_column');
            }

            if (! Schema::hasColumn('workorders', 'arrival_box_recorded_by')) {
                $table->foreignId('arrival_box_recorded_by')
                    ->nullable()
                    ->after('arrival_box_status')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('workorders', 'arrival_box_recorded_at')) {
                $table->timestamp('arrival_box_recorded_at')->nullable()->after('arrival_box_recorded_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table): void {
            if (Schema::hasColumn('workorders', 'arrival_box_recorded_by')) {
                $table->dropConstrainedForeignId('arrival_box_recorded_by');
            }

            if (Schema::hasColumn('workorders', 'arrival_box_recorded_at')) {
                $table->dropColumn('arrival_box_recorded_at');
            }

            if (Schema::hasColumn('workorders', 'arrival_box_status')) {
                $table->dropColumn('arrival_box_status');
            }
        });
    }
};
