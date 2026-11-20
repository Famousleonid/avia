<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            $table->json('specification_descriptions')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tdr_processes', fn (Blueprint $table) => $table->dropColumn('specification_descriptions'));
    }
};
