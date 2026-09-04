<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workorder_media_sequences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workorder_id')->constrained('workorders')->cascadeOnDelete();
            $table->string('collection_name', 100);
            $table->unsignedBigInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->unique(
                ['workorder_id', 'collection_name'],
                'workorder_media_sequences_folder_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workorder_media_sequences');
    }
};
