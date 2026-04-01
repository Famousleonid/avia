<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wo_bushing_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workorder_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('process_id')->nullable()->constrained()->nullOnDelete();
            $table->string('repair_order')->nullable();
            $table->date('date_start')->nullable();
            $table->date('date_finish')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wo_bushing_batches');
    }
};
