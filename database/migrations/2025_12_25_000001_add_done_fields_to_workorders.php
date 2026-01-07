<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            // Done строго по Completed.date_finish
            $table->date('done_at')->nullable()->after('approve_at');
            $table->foreignId('done_user_id')->nullable()->after('done_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('done_user_id');
            $table->dropColumn('done_at');
        });
    }
};
