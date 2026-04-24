<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('date_notifications', function (Blueprint $table) {
            $table->boolean('repeats_yearly')->default(true)->after('run_day');
            $table->unsignedSmallInteger('run_year')->nullable()->after('repeats_yearly');
        });
    }

    public function down(): void
    {
        Schema::table('date_notifications', function (Blueprint $table) {
            $table->dropColumn(['repeats_yearly', 'run_year']);
        });
    }
};
