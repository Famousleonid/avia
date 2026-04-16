<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            if (! Schema::hasColumn('tdr_processes', 'date_start_user_id')) {
                $table->foreignId('date_start_user_id')
                    ->nullable()
                    ->after('date_start')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('tdr_processes', 'date_finish_user_id')) {
                $table->foreignId('date_finish_user_id')
                    ->nullable()
                    ->after('date_finish')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            if (Schema::hasColumn('tdr_processes', 'date_start_user_id')) {
                $table->dropConstrainedForeignId('date_start_user_id');
            }

            if (Schema::hasColumn('tdr_processes', 'date_finish_user_id')) {
                $table->dropConstrainedForeignId('date_finish_user_id');
            }
        });
    }
};
