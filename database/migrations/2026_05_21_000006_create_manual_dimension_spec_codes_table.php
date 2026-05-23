<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Restricts which finding codes are allowed for a given spec.
        // If empty for a spec — all codes are permitted.
        Schema::create('manual_dimension_spec_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_dimension_spec_id')
                ->constrained('manual_dimension_specs')
                ->onDelete('cascade');
            $table->foreignId('codes_id')
                ->constrained('codes')
                ->onDelete('cascade');

            $table->unique(['manual_dimension_spec_id', 'codes_id'], 'dim_spec_codes_unique');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('manual_dimension_spec_codes');
        Schema::enableForeignKeyConstraints();
    }
};
