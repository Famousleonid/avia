<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workorder_kit_prl_crossouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workorder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['workorder_id', 'component_id'],
                'wo_kit_prl_crossouts_wo_component_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workorder_kit_prl_crossouts');
    }
};
