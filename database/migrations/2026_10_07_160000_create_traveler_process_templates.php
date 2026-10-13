<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('traveler_process_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->constrained()->cascadeOnDelete();
            $table->string('part_number');
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->index(['manual_id', 'part_number']);
        });
        Schema::create('traveler_process_template_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('traveler_process_templates')->cascadeOnDelete();
            $table->foreignId('process_id')->constrained('processes')->restrictOnDelete();
            $table->unsignedInteger('sort_order');
            $table->unsignedInteger('traveler_group')->nullable();
            $table->text('description')->nullable();
        });
        // An application is a snapshot/audit record, never a live link to WO processes.
        Schema::create('tdr_traveler_template_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tdr_id')->constrained('tdrs')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('traveler_process_templates')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('revision');
            $table->json('snapshot');
            $table->json('process_ids');
            $table->timestamps();
            $table->unique(['tdr_id', 'template_id'], 'tdr_traveler_template_once');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdr_traveler_template_applications');
        Schema::dropIfExists('traveler_process_template_steps');
        Schema::dropIfExists('traveler_process_templates');
    }
};
