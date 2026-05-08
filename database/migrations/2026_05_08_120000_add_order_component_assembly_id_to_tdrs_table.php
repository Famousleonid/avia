<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdrs', function (Blueprint $table): void {
            $table->foreignId('order_component_assembly_id')
                ->nullable()
                ->after('order_component_id')
                ->constrained('component_assemblies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tdrs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('order_component_assembly_id');
        });
    }
};
