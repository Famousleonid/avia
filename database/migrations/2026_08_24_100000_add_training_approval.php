<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Приёмка тренингов: approved_by/approved_at на записи тренинга.
 * Принятая запись заморожена (ни удалить, ни сменить дату) для всех,
 * кроме назначенных (users.can_manage_approved_trainings; назначен Voronin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('is_legacy')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_manage_approved_trainings')->default(false)->after('can_sign_certificates');
        });

        DB::table('users')
            ->where('name', 'like', '%Voronin%')
            ->whereNull('deleted_at')
            ->update(['can_manage_approved_trainings' => true]);
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn('approved_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_manage_approved_trainings');
        });
    }
};
