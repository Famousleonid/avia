<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('manual_part_group_coverages', 'expand_ipl_family')) {
            Schema::table('manual_part_group_coverages', function (Blueprint $table): void {
                $table->boolean('expand_ipl_family')->default(true);
            });
        }
    }

    public function down(): void
    {
        Schema::table('manual_part_group_coverages', function (Blueprint $table): void {
            $table->dropColumn('expand_ipl_family');
        });
    }
};
