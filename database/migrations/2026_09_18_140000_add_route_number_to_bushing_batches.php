<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wo_bushing_batches', function (Blueprint $table) {
            $table->unsignedInteger('route_number')->nullable()->index();
            $table->unsignedInteger('legacy_number')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wo_bushing_batches', fn (Blueprint $table) => $table->dropColumn(['route_number', 'legacy_number']));
    }
};
