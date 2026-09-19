<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // The user may have installed this exact schema via the SQL-only handoff.
        if (Schema::hasTable('manual_in_process_check_sheets')) {
            return;
        }
        Schema::create('manual_in_process_check_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->unique()->constrained('manuals')->restrictOnDelete();
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->string('source_file');
            $table->string('source_sheet');
            $table->char('source_sha256', 64);
            $table->char('content_sha256', 64);
            $table->json('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_in_process_check_sheets');
    }
};
