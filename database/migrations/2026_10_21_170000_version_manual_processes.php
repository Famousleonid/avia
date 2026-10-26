<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('manual_processes', 'deleted_at')) {
            Schema::table('manual_processes', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
        if (!Schema::hasColumn('manual_processes', 'archived_by')) {
            Schema::table('manual_processes', fn (Blueprint $table) => $table->unsignedBigInteger('archived_by')->nullable());
        }
        if (!Schema::hasTable('manual_process_versions')) {
            Schema::create('manual_process_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('manual_process_id');
                $table->unsignedBigInteger('process_id');
                $table->unsignedInteger('version');
                $table->text('process_comment')->nullable();
                $table->boolean('requires_fig')->default(false);
                $table->boolean('requires_zone')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->unique(['manual_process_id', 'version']);
                $table->index('process_id');
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Process history must not be dropped. Restore a reviewed backup instead.');
    }
};
