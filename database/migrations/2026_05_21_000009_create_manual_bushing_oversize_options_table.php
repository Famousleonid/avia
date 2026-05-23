<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catalog of available oversize values for ordering.
        // oversize_rounding on bushing_spec determines which option is selected.
        Schema::create('manual_bushing_oversize_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_bushing_spec_id')
                ->constrained('manual_bushing_specs')
                ->onDelete('cascade');
            $table->decimal('oversize_value', 6, 4);
            $table->string('part_number')->nullable();
            $table->string('description')->nullable();

            $table->unique(['manual_bushing_spec_id', 'oversize_value'], 'bushing_oversize_options_unique');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('manual_bushing_oversize_options');
        Schema::enableForeignKeyConstraints();
    }
};
