<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A manual (CMM) may apply to SEVERAL aircraft of one builder (e.g. the
     * same unit flies on E170 and E175). manual_plane is the many-to-many
     * source of truth for that set; manuals.planes_id stays as the denormalized
     * "primary" (first) plane so legacy readers (marketing/sales/activity log)
     * keep working unchanged.
     *
     * Backfill: every manual's current single plane becomes its one-element set,
     * so behaviour is identical until someone actually adds a second plane.
     */
    public function up(): void
    {
        Schema::create('manual_plane', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->constrained('manuals')->cascadeOnDelete();
            $table->foreignId('plane_id')->constrained('planes')->cascadeOnDelete();
            $table->unique(['manual_id', 'plane_id']);
        });

        DB::statement('
            INSERT INTO manual_plane (manual_id, plane_id)
            SELECT id, planes_id FROM manuals WHERE planes_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_plane');
    }
};
