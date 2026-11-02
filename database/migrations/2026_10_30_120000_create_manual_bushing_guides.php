<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('manual_bushing_guides', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->json('document');
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
        });
        Schema::create('manual_bushing_guide_manual', function (Blueprint $table) {
            $table->unsignedBigInteger('guide_id');
            $table->unsignedBigInteger('manual_id');
            $table->primary(['guide_id', 'manual_id']);
            $table->index('manual_id');
        });
        // Keep the cell history independently of the rotating general activity log.
        Schema::create('manual_bushing_guide_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guide_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name');
            $table->unsignedInteger('revision');
            $table->string('event');
            $table->json('changes');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_bushing_guide_changes');
        Schema::dropIfExists('manual_bushing_guide_manual');
        Schema::dropIfExists('manual_bushing_guides');
    }
};
