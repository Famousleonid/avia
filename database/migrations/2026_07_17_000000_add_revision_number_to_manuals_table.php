<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manuals', function (Blueprint $table): void {
            $table->string('revision_number')->nullable()->after('lib');
        });
    }

    public function down(): void
    {
        Schema::table('manuals', function (Blueprint $table): void {
            $table->dropColumn('revision_number');
        });
    }
};
