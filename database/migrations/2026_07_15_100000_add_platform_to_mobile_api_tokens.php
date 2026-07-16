<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mobile_api_tokens', function (Blueprint $table) {
            // 'android' | 'ios'; null = legacy iOS tokens issued before the column existed
            $table->string('platform', 16)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('mobile_api_tokens', function (Blueprint $table) {
            $table->dropColumn('platform');
        });
    }
};
