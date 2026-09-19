<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('components', 'kit_prl_choice_group')) {
            return;
        }

        if (DB::table('components')->whereNotNull('kit_prl_choice_group')
            ->where('kit_prl_choice_group', '<>', '')->exists()) {
            throw new RuntimeException('Legacy links remain. Audit and run part-groups:retire-legacy --apply before removing the legacy column.');
        }

        Schema::table('components', function (Blueprint $table): void {
            $table->dropIndex('components_manual_kit_prl_choice_group_idx');
            $table->dropColumn('kit_prl_choice_group');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('components', 'kit_prl_choice_group')) {
            Schema::table('components', function (Blueprint $table): void {
                $table->string('kit_prl_choice_group', 100)->nullable();
                $table->index(['manual_id', 'kit_prl_choice_group'], 'components_manual_kit_prl_choice_group_idx');
            });
        }
        // Schema rollback never recreates retired links or changes Part Groups.
    }
};
