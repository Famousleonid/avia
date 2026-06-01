<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quantum_ro_lines', function (Blueprint $table): void {
            $table->timestamp('bom_last_modified')->nullable()->after('detail_last_modified');
        });
    }

    public function down(): void
    {
        Schema::table('quantum_ro_lines', function (Blueprint $table): void {
            $table->dropColumn('bom_last_modified');
        });
    }
};
