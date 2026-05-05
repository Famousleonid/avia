<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('log_cards', function (Blueprint $table) {
            $table->json('component_data_out')->nullable()->after('component_data');
            $table->json('destruction_certificate_data')->nullable()->after('component_data_out');
        });
    }

    public function down(): void
    {
        Schema::table('log_cards', function (Blueprint $table) {
            $table->dropColumn(['component_data_out', 'destruction_certificate_data']);
        });
    }
};
