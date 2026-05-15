<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('std_processes', 'component_id')) {
            Schema::table('std_processes', function (Blueprint $table): void {
                $table->foreignId('component_id')
                    ->nullable()
                    ->after('manual_id')
                    ->constrained('components')
                    ->cascadeOnDelete();
            });
        }

        $this->removeDuplicateActiveComponents();
        $this->backfillStdProcessComponentIds();
        $this->removeOrphanAndDuplicateStdRows();

        Schema::table('std_processes', function (Blueprint $table): void {
            $table->unique(['component_id', 'std'], 'std_processes_component_std_unique');
            $table->index(['manual_id', 'std', 'component_id'], 'std_processes_manual_std_component_idx');
        });

        Schema::table('std_processes', function (Blueprint $table): void {
            foreach (['ipl_num', 'part_number', 'description'] as $column) {
                if (Schema::hasColumn('std_processes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasTable('workorder_std_process_items')) {
            DB::table('workorder_std_process_items')->delete();
        }
    }

    public function down(): void
    {
        Schema::table('std_processes', function (Blueprint $table): void {
            $table->string('ipl_num', 64)->default('')->after('std');
            $table->string('part_number', 255)->default('')->after('ipl_num');
            $table->text('description')->nullable()->after('part_number');
        });

        DB::table('std_processes')
            ->join('components', 'components.id', '=', 'std_processes.component_id')
            ->update([
                'std_processes.ipl_num' => DB::raw('components.ipl_num'),
                'std_processes.part_number' => DB::raw('components.part_number'),
                'std_processes.description' => DB::raw('components.name'),
            ]);

        Schema::table('std_processes', function (Blueprint $table): void {
            $table->dropUnique('std_processes_component_std_unique');
            $table->dropIndex('std_processes_manual_std_component_idx');
            $table->dropConstrainedForeignId('component_id');
        });
    }

    private function removeDuplicateActiveComponents(): void
    {
        $duplicates = DB::table('components')
            ->select('manual_id', DB::raw('trim(ipl_num) as normalized_ipl'), DB::raw('min(id) as keep_id'))
            ->whereNull('deleted_at')
            ->whereNotNull('manual_id')
            ->whereNotNull('ipl_num')
            ->whereRaw("trim(ipl_num) <> ''")
            ->groupBy('manual_id', DB::raw('trim(ipl_num)'))
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('components')
                ->where('manual_id', $duplicate->manual_id)
                ->whereRaw('trim(ipl_num) = ?', [$duplicate->normalized_ipl])
                ->where('id', '<>', $duplicate->keep_id)
                ->delete();
        }
    }

    private function backfillStdProcessComponentIds(): void
    {
        if (! Schema::hasColumn('std_processes', 'ipl_num')) {
            return;
        }

        DB::table('std_processes')
            ->whereNull('component_id')
            ->orderBy('id')
            ->get(['id', 'manual_id', 'ipl_num'])
            ->each(function ($row): void {
                $componentId = DB::table('components')
                    ->whereNull('deleted_at')
                    ->where('manual_id', $row->manual_id)
                    ->whereRaw('trim(ipl_num) = ?', [trim((string) $row->ipl_num)])
                    ->orderBy('id')
                    ->value('id');

                if ($componentId) {
                    DB::table('std_processes')
                        ->where('id', $row->id)
                        ->update(['component_id' => $componentId]);
                }
            });
    }

    private function removeOrphanAndDuplicateStdRows(): void
    {
        DB::table('std_processes')
            ->whereNull('component_id')
            ->delete();

        $duplicates = DB::table('std_processes')
            ->select('component_id', 'std', DB::raw('min(id) as keep_id'))
            ->whereNotNull('component_id')
            ->groupBy('component_id', 'std')
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('std_processes')
                ->where('component_id', $duplicate->component_id)
                ->where('std', $duplicate->std)
                ->where('id', '<>', $duplicate->keep_id)
                ->delete();
        }
    }
};
