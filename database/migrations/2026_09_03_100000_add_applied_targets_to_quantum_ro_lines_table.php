<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quantum_ro_lines', function (Blueprint $table): void {
            $table->json('applied_targets')->nullable()->after('applied_target_id');
        });
    }

    public function down(): void
    {
        Schema::table('quantum_ro_lines', function (Blueprint $table): void {
            $table->dropColumn('applied_targets');
        });
    }
};
