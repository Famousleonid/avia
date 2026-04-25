<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workorder_tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workorder_id')->constrained()->cascadeOnDelete();
            $table->string('tool_key', 120);
            $table->json('input_values');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['workorder_id', 'tool_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workorder_tools');
    }
};
