<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wo_bushing_lines', function (Blueprint $table) {
            $table->boolean('selection_active')->default(true);
        });
        Schema::create('workorder_bushing_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workorder_id')->constrained()->cascadeOnDelete();
            $table->string('family_key', 191);
            $table->timestamps();
            $table->unique(['workorder_id', 'family_key'], 'wo_bushing_decision_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workorder_bushing_decisions');
        Schema::table('wo_bushing_lines', fn (Blueprint $table) => $table->dropColumn('selection_active'));
    }
};
