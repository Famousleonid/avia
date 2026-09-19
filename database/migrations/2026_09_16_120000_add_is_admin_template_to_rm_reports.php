<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rm_reports', function (Blueprint $table) {
            $table->boolean('is_admin_template')->default(false);
        });
    }
    public function down(): void
    {
        Schema::table('rm_reports', fn (Blueprint $table) => $table->dropColumn('is_admin_template'));
    }
};
