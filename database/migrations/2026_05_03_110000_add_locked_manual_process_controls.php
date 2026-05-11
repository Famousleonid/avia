<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'can_manage_locked_manual_processes')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('can_manage_locked_manual_processes')
                    ->default(false)
                    ->after('is_admin');
            });
        }

        Schema::table('manual_processes', function (Blueprint $table) {
            if (! Schema::hasColumn('manual_processes', 'is_locked')) {
                $table->boolean('is_locked')
                    ->default(false)
                    ->after('processes_id');
            }

            if (! Schema::hasColumn('manual_processes', 'locked_by_user_id')) {
                $table->foreignId('locked_by_user_id')
                    ->nullable()
                    ->after('is_locked')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('manual_processes', 'locked_at')) {
                $table->timestamp('locked_at')
                    ->nullable()
                    ->after('locked_by_user_id');
            }
        });

        if (! Schema::hasTable('manual_process_name_locks')) {
            Schema::create('manual_process_name_locks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('manual_id')->constrained()->cascadeOnDelete();
                $table->foreignId('process_name_id')->constrained('process_names')->cascadeOnDelete();
                $table->foreignId('locked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('locked_at')->nullable();
                $table->timestamps();

                $table->unique(['manual_id', 'process_name_id'], 'manual_process_name_locks_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_process_name_locks');

        Schema::table('manual_processes', function (Blueprint $table) {
            if (Schema::hasColumn('manual_processes', 'locked_by_user_id')) {
                $table->dropConstrainedForeignId('locked_by_user_id');
            }

            $columnsToDrop = array_values(array_filter([
                Schema::hasColumn('manual_processes', 'is_locked') ? 'is_locked' : null,
                Schema::hasColumn('manual_processes', 'locked_at') ? 'locked_at' : null,
            ]));

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });

        if (Schema::hasColumn('users', 'can_manage_locked_manual_processes')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('can_manage_locked_manual_processes');
            });
        }
    }
};
