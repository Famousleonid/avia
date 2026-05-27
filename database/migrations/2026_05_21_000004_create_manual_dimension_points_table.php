<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_dimension_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_dimension_figure_id')
                ->constrained('manual_dimension_figures')
                ->onDelete('cascade');
            $table->enum('point_type', ['navigation', 'measurement', 'circle', 'text'])->default('measurement');
            $table->unsignedBigInteger('child_figure_id')->nullable();
            $table->unsignedBigInteger('child_ic_id')->nullable();
            $table->string('code');
            $table->string('description')->nullable();
            $table->decimal('x_pct', 5, 2);
            $table->decimal('y_pct', 5, 2);
            $table->decimal('width_pct', 5, 2)->nullable();
            $table->decimal('height_pct', 5, 2)->nullable();
            $table->decimal('x2_pct', 5, 2)->nullable();
            $table->decimal('y2_pct', 5, 2)->nullable();
            // external label position (callout, circle, area, line)
            $table->decimal('label_x_pct', 5, 2)->nullable();
            $table->decimal('label_y_pct', 5, 2)->nullable();
            $table->boolean('is_fits_clearance')->default(false);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('child_figure_id')
                ->references('id')->on('manual_dimension_figures')
                ->onDelete('set null');

            $table->foreign('child_ic_id')
                ->references('id')->on('manual_inspection_components')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('manual_dimension_points');
        Schema::enableForeignKeyConstraints();
    }
};
