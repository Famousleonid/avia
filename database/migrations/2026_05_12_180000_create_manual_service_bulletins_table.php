<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_service_bulletins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('year_introduced')->nullable();
            $table->string('ac_mfg_service_bulletin_no')->nullable();
            $table->string('oem_service_bulletin_no')->nullable();
            $table->string('awd_no')->nullable();
            $table->string('identification_method')->nullable();
            $table->text('description')->nullable();
            $table->string('default_requirement')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['manual_id', 'is_active', 'sort_order'], 'manual_sb_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_service_bulletins');
    }
};
