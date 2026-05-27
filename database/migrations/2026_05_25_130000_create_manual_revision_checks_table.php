<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_revision_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->constrained('manuals')->cascadeOnDelete();
            $table->string('revision_number')->nullable();
            $table->date('revision_date')->nullable();
            $table->date('checked_at');
            $table->foreignId('checked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('unchanged');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['manual_id', 'checked_at']);
            $table->index(['checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_revision_checks');
    }
};
