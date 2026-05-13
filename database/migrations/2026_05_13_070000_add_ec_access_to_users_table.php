<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'ec_access')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('ec_access')->default(false)->after('qa_access');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'ec_access')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('ec_access');
            });
        }
    }
};
