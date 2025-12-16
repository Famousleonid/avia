<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_tasks', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('name');
            $table->index('sort_order');
            $table->boolean('has_start_date')->default(true);
        });

    }

    public function down(): void
    {
        Schema::table('general_tasks', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
