<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('manuals', fn (Blueprint $table) => $table->json('log_card_order')->nullable());
    }
    public function down(): void
    {
        Schema::table('manuals', fn (Blueprint $table) => $table->dropColumn('log_card_order'));
    }
};
