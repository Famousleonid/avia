<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('traveler_note_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->constrained()->cascadeOnDelete();
            $table->string('part_number');
            $table->foreignId('process_names_id')->constrained('process_names')->cascadeOnDelete();
            $table->text('notes');
            $table->timestamps();
            $table->unique(['manual_id', 'part_number', 'process_names_id'], 'traveler_note_match_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traveler_note_templates');
    }
};
