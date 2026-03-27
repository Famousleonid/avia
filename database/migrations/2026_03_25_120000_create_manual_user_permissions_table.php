<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->constrained('manuals')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['manual_id', 'user_id'], 'manual_user_permissions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_user_permissions');
    }
};
