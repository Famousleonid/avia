<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->unsignedInteger('paint_queue_order')->nullable()->after('place');
        });
    }

    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->dropColumn('paint_queue_order');
        });
    }
};
