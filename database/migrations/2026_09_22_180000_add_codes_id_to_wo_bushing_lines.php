<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wo_bushing_lines', function (Blueprint $table) {
            $table->foreignId('codes_id')->nullable()->constrained('codes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wo_bushing_lines', fn (Blueprint $table) => $table->dropConstrainedForeignId('codes_id'));
    }
};
