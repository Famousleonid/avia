<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quantum_ro_lines', function (Blueprint $table) {
            $table->string('bom_ref')->nullable()->after('class')->index();
        });
    }

    public function down(): void
    {
        Schema::table('quantum_ro_lines', function (Blueprint $table) {
            $table->dropIndex(['bom_ref']);
            $table->dropColumn('bom_ref');
        });
    }
};
