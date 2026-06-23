<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-member Fits & Clearances reference (FIGURE 8001 NUMBER).
     *
     * Table 8001 numbers each MEMBER of a pair on its own row (e.g. Bolt = 1,
     * Bushing = 2). The existing manual_fits.ref_no holds the OD member's
     * reference; this adds the ID member's reference. When id_ref_no is null or
     * equal to ref_no, the F&C table renders the pair with a single merged
     * Ref.No cell (legacy look); when they differ, each member is its own
     * numbered row, sorted by Ref.No (assembly/permitted clearance stays merged
     * across the pair).
     */
    public function up(): void
    {
        Schema::table('manual_fits', function (Blueprint $table) {
            $table->string('id_ref_no', 40)->nullable()->after('ref_no');
        });
    }

    public function down(): void
    {
        Schema::table('manual_fits', function (Blueprint $table) {
            $table->dropColumn('id_ref_no');
        });
    }
};
