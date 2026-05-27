<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_revision_checks', function (Blueprint $table) {
            $table->string('checked_by_stamp', 20)->nullable()->after('checked_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('manual_revision_checks', function (Blueprint $table) {
            $table->dropColumn('checked_by_stamp');
        });
    }
};
