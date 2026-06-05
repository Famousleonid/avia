<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table): void {
            $table->string('kit_prl_choice_group', 100)
                ->nullable()
                ->after('kit');
            $table->index(['manual_id', 'kit_prl_choice_group'], 'components_manual_kit_prl_choice_group_idx');
        });

        $manualIds = DB::table('manuals')
            ->where('number', '32-11-05 ELEB')
            ->pluck('id');

        if ($manualIds->isNotEmpty()) {
            DB::table('components')
                ->whereIn('manual_id', $manualIds)
                ->whereIn('ipl_num', ['1-320', '1-321', '1-321A'])
                ->update(['kit_prl_choice_group' => 'bearing_spherical_320_321']);
        }
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table): void {
            $table->dropIndex('components_manual_kit_prl_choice_group_idx');
            $table->dropColumn('kit_prl_choice_group');
        });
    }
};
