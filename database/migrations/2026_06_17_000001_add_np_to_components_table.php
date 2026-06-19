<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table): void {
            if (! Schema::hasColumn('components', 'np')) {
                $table->boolean('np')->default(false)->after('kit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table): void {
            if (Schema::hasColumn('components', 'np')) {
                $table->dropColumn('np');
            }
        });
    }
};
