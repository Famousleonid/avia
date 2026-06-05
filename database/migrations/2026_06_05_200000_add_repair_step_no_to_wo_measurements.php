<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A FINAL measurement that lands within an oversize repair step PASSes — record
 * which step (step_no) so the UI can show e.g. "RO5 · PASS".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wo_measurements', function (Blueprint $table) {
            $table->unsignedSmallInteger('repair_step_no')->nullable()->after('result');
        });
    }

    public function down(): void
    {
        Schema::table('wo_measurements', function (Blueprint $table) {
            $table->dropColumn('repair_step_no');
        });
    }
};
