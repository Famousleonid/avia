<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('workorders')) {
            return;
        }

        if (!Schema::hasColumn('workorders', 'machining_queue_order')) {
            Schema::table('workorders', function (Blueprint $table): void {
                $table->unsignedInteger('machining_queue_order')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('workorders')) {
            return;
        }

        if (Schema::hasColumn('workorders', 'machining_queue_order')) {
            Schema::table('workorders', function (Blueprint $table): void {
                $table->dropColumn('machining_queue_order');
            });
        }
    }
};

