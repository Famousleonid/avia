<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wo_bushing_processes', function (Blueprint $table) {
            $table->foreignId('batch_id')
                ->nullable()
                ->after('process_id')
                ->constrained('wo_bushing_batches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wo_bushing_processes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
        });
    }
};
