<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('std_processes', function (Blueprint $table): void {
            $table->index('component_id', 'std_processes_component_id_idx');
            $table->dropUnique('std_processes_component_std_unique');
            $table->unique(['manual_id', 'component_id', 'std'], 'std_processes_manual_component_std_unique');
        });
    }

    public function down(): void
    {
        Schema::table('std_processes', function (Blueprint $table): void {
            $table->dropUnique('std_processes_manual_component_std_unique');
            $table->unique(['component_id', 'std'], 'std_processes_component_std_unique');
            $table->dropIndex('std_processes_component_id_idx');
        });
    }
};
