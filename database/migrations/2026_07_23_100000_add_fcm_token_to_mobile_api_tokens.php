<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mobile_api_tokens', function (Blueprint $table) {
            // FCM device token lives on the session row: dies with logout,
            // several rows per user = several devices.
            $table->string('fcm_token', 512)->nullable()->after('platform');
        });
    }

    public function down(): void
    {
        Schema::table('mobile_api_tokens', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });
    }
};
