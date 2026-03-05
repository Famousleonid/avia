<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_names', function (Blueprint $table) {
            $table->unsignedInteger('std_days')->nullable()->after('form_number');

            $table->foreignId('notify_user_id')
                ->nullable()
                ->after('std_days')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('process_names', function (Blueprint $table) {
            $table->dropForeign(['notify_user_id']);
            $table->dropColumn(['std_days', 'notify_user_id']);
        });
    }
};
