<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('traveler_template_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('traveler_process_templates')->cascadeOnDelete();
            $table->string('path');
            $table->string('name');
            $table->string('mime', 50);
            $table->unsignedInteger('sort_order');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['template_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traveler_template_images');
    }
};
