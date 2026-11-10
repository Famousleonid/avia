<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wo_bushing_batches', function (Blueprint $table) {
            $table->string('route_label', 40)->nullable();
            $table->boolean('manual_partition')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('wo_bushing_batches', fn (Blueprint $table) => $table->dropColumn(['route_label', 'manual_partition']));
    }
};
