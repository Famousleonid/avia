<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_dimension_figures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('parent_figure_id')->nullable();
            $table->enum('figure_type', ['overview', 'detail'])->default('detail');
            $table->string('title');
            $table->string('image_path');
            $table->unsignedInteger('image_width')->nullable();
            $table->unsignedInteger('image_height')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('parent_figure_id')
                ->references('id')->on('manual_dimension_figures')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('manual_dimension_figures');
        Schema::enableForeignKeyConstraints();
    }
};
