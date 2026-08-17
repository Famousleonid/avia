<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('manual_part_groups')
            ->where('type', 'sb_kit')
            ->update(['type' => 'kit']);

        DB::statement('ALTER TABLE manual_part_group_coverages MODIFY component_id BIGINT UNSIGNED NULL');

        if (! $this->hasIndex('manual_part_group_coverages_option_fk_idx')) {
            Schema::table('manual_part_group_coverages', function (Blueprint $table): void {
                $table->index('manual_part_group_option_id', 'manual_part_group_coverages_option_fk_idx');
            });
        }
        if ($this->hasIndex('manual_part_group_coverages_option_component_unique')) {
            DB::statement('ALTER TABLE manual_part_group_coverages DROP INDEX manual_part_group_coverages_option_component_unique');
        }
        if (! Schema::hasColumn('manual_part_group_coverages', 'covered_manual_part_group_option_id')) {
            Schema::table('manual_part_group_coverages', function (Blueprint $table): void {
                $table->unsignedBigInteger('covered_manual_part_group_option_id')->nullable()->after('component_id');
            });
        }
        if (! $this->hasForeign('manual_part_group_coverages_nested_option_fk')) {
            Schema::table('manual_part_group_coverages', function (Blueprint $table): void {
                $table->foreign('covered_manual_part_group_option_id', 'manual_part_group_coverages_nested_option_fk')
                    ->references('id')->on('manual_part_group_options')->cascadeOnDelete();
            });
        }
        if (! $this->hasIndex('manual_part_group_coverages_option_component_unique')) {
            Schema::table('manual_part_group_coverages', function (Blueprint $table): void {
                $table->unique(['manual_part_group_option_id', 'component_id'], 'manual_part_group_coverages_option_component_unique');
            });
        }
        if (! $this->hasIndex('manual_part_group_coverages_option_nested_unique')) {
            Schema::table('manual_part_group_coverages', function (Blueprint $table): void {
                $table->unique(['manual_part_group_option_id', 'covered_manual_part_group_option_id'], 'manual_part_group_coverages_option_nested_unique');
            });
        }
    }

    public function down(): void
    {
        DB::table('manual_part_group_coverages')
            ->whereNotNull('covered_manual_part_group_option_id')
            ->delete();

        if ($this->hasIndex('manual_part_group_coverages_option_nested_unique')) {
            DB::statement('ALTER TABLE manual_part_group_coverages DROP INDEX manual_part_group_coverages_option_nested_unique');
        }
        if ($this->hasIndex('manual_part_group_coverages_option_component_unique')) {
            DB::statement('ALTER TABLE manual_part_group_coverages DROP INDEX manual_part_group_coverages_option_component_unique');
        }
        if ($this->hasForeign('manual_part_group_coverages_nested_option_fk')) {
            DB::statement('ALTER TABLE manual_part_group_coverages DROP FOREIGN KEY manual_part_group_coverages_nested_option_fk');
        }
        if (Schema::hasColumn('manual_part_group_coverages', 'covered_manual_part_group_option_id')) {
            Schema::table('manual_part_group_coverages', function (Blueprint $table): void {
                $table->dropColumn('covered_manual_part_group_option_id');
            });
        }

        DB::statement('ALTER TABLE manual_part_group_coverages MODIFY component_id BIGINT UNSIGNED NOT NULL');

        if (! $this->hasIndex('manual_part_group_coverages_option_component_unique')) {
            Schema::table('manual_part_group_coverages', function (Blueprint $table): void {
                $table->unique(['manual_part_group_option_id', 'component_id'], 'manual_part_group_coverages_option_component_unique');
            });
        }
        if ($this->hasIndex('manual_part_group_coverages_option_fk_idx')) {
            DB::statement('ALTER TABLE manual_part_group_coverages DROP INDEX manual_part_group_coverages_option_fk_idx');
        }

        DB::table('manual_part_groups')
            ->where('type', 'kit')
            ->update(['type' => 'sb_kit']);
    }

    private function hasIndex(string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'manual_part_group_coverages')
            ->where('index_name', $index)
            ->exists();
    }

    private function hasForeign(string $constraint): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::getDatabaseName())
            ->where('table_name', 'manual_part_group_coverages')
            ->where('constraint_name', $constraint)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
