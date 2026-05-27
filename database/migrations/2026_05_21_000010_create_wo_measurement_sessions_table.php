<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sessions concept removed — measurements are tied directly to workorders.
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('wo_measurement_sessions');
        Schema::enableForeignKeyConstraints();
    }
};
