<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workorder_service_bulletin_logs')) {
            Schema::table('workorder_service_bulletin_logs', function (Blueprint $table) {
                if (! $this->indexExists('workorder_service_bulletin_logs', 'wo_sb_log_unique')) {
                    $table->unique(['workorder_id', 'manual_service_bulletin_id'], 'wo_sb_log_unique');
                }

                if (! $this->foreignKeyExists('workorder_service_bulletin_logs', 'wo_sb_log_bulletin_fk')) {
                    $table->foreign('manual_service_bulletin_id', 'wo_sb_log_bulletin_fk')
                        ->references('id')
                        ->on('manual_service_bulletins')
                        ->cascadeOnDelete();
                }
            });

            return;
        }

        Schema::create('workorder_service_bulletin_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workorder_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('manual_service_bulletin_id');
            $table->string('status')->nullable();
            $table->foreignId('stamp_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('stamped_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['workorder_id', 'manual_service_bulletin_id'], 'wo_sb_log_unique');
            $table->foreign('manual_service_bulletin_id', 'wo_sb_log_bulletin_fk')
                ->references('id')
                ->on('manual_service_bulletins')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workorder_service_bulletin_logs');
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', $constraint)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
