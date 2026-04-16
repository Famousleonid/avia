<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            if (!Schema::hasColumn('media', 'archive_synced_at')) {
                $table->timestamp('archive_synced_at')->nullable()->after('order_column')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            if (Schema::hasColumn('media', 'archive_synced_at')) {
                $table->dropIndex(['archive_synced_at']);
                $table->dropColumn('archive_synced_at');
            }
        });
    }
};
