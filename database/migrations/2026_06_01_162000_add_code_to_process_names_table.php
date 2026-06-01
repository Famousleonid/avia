<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_names', function (Blueprint $table): void {
            $table->string('code', 80)->nullable()->after('name')->unique();
        });
    }

    public function down(): void
    {
        Schema::table('process_names', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
