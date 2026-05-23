<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_ui_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 120);
            $table->string('key', 120);
            $table->json('value');
            $table->timestamps();

            $table->unique(['user_id', 'scope', 'key']);
            $table->index(['scope', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ui_settings');
    }
};
