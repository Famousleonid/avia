<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wo_bushing_lines', function (Blueprint $table): void {
            $table->boolean('do_not_order')->default(false)->after('qty_remaining');
        });
    }

    public function down(): void
    {
        Schema::table('wo_bushing_lines', function (Blueprint $table): void {
            $table->dropColumn('do_not_order');
        });
    }
};
