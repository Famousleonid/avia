<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machining_work_steps', function (Blueprint $table) {
            if (! Schema::hasColumn('machining_work_steps', 'description')) {
                $table->text('description')->nullable()->after('date_finish');
            }
        });
    }

    public function down(): void
    {
        Schema::table('machining_work_steps', function (Blueprint $table) {
            if (Schema::hasColumn('machining_work_steps', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
