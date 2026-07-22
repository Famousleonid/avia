<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_processes', function (Blueprint $table): void {
            $table->boolean('requires_fig')->default(false)->after('process_comment');
            $table->boolean('requires_zone')->default(false)->after('requires_fig');
        });

        Schema::table('tdr_processes', function (Blueprint $table): void {
            $table->boolean('requires_fig')->default(false)->after('description');
            $table->boolean('requires_zone')->default(false)->after('requires_fig');
        });
    }

    public function down(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table): void {
            $table->dropColumn(['requires_fig', 'requires_zone']);
        });

        Schema::table('manual_processes', function (Blueprint $table): void {
            $table->dropColumn(['requires_fig', 'requires_zone']);
        });
    }
};
