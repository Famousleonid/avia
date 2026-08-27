<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §5 (stage 5): in-process checks — a parameter measured MID-ROUTE, not at the
 * incoming inspection (base metal diameter after chrome strip). Such points do
 * not block the Update button's "all initials entered" gate and are grouped
 * separately in the Inspect panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_parameters', function (Blueprint $table) {
            $table->boolean('in_process')->default(false)->after('inspection');
        });
    }

    public function down(): void
    {
        Schema::table('manual_parameters', function (Blueprint $table) {
            $table->dropColumn('in_process');
        });
    }
};
