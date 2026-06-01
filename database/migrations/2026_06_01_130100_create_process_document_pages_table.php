<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_document_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                  ->constrained('process_documents')
                  ->cascadeOnDelete();
            $table->unsignedSmallInteger('page_no')->default(1);
            $table->string('image_path')->nullable();
            $table->unsignedInteger('image_width')->nullable();
            $table->unsignedInteger('image_height')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_document_pages');
    }
};
