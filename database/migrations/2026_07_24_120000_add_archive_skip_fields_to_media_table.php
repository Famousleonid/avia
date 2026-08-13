<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            if (!Schema::hasColumn('media', 'archive_skipped_at')) {
                $table->timestamp('archive_skipped_at')
                    ->nullable()
                    ->after('archive_synced_at')
                    ->index();
            }

            if (!Schema::hasColumn('media', 'archive_skip_reason')) {
                $table->string('archive_skip_reason')
                    ->nullable()
                    ->after('archive_skipped_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            if (Schema::hasColumn('media', 'archive_skip_reason')) {
                $table->dropColumn('archive_skip_reason');
            }

            if (Schema::hasColumn('media', 'archive_skipped_at')) {
                $table->dropIndex(['archive_skipped_at']);
                $table->dropColumn('archive_skipped_at');
            }
        });
    }
};
