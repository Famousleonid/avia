<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['tdr_processes', 'workorder_std_processes'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                if (! Schema::hasColumn($table, 'date_start_user')) {
                    $blueprint->string('date_start_user', 120)->nullable()->after('date_start_user_id');
                }

                if (! Schema::hasColumn($table, 'date_finish_user')) {
                    $blueprint->string('date_finish_user', 120)->nullable()->after('date_finish_user_id');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['tdr_processes', 'workorder_std_processes'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $columns = [];

                foreach (['date_start_user', 'date_finish_user'] as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $columns[] = $column;
                    }
                }

                if ($columns !== []) {
                    $blueprint->dropColumn($columns);
                }
            });
        }
    }
};
