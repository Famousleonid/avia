<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quantum_ro_lines', function (Blueprint $table): void {
            $table->string('serial_number')->nullable()->after('pn')->index();
        });
    }

    public function down(): void
    {
        Schema::table('quantum_ro_lines', function (Blueprint $table): void {
            $table->dropIndex(['serial_number']);
            $table->dropColumn('serial_number');
        });
    }
};
