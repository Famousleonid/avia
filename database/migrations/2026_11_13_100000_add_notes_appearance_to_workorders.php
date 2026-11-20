<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->json('notes_appearance')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('workorders', fn (Blueprint $table) => $table->dropColumn('notes_appearance'));
    }
};
