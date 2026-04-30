<?php

use App\Models\MachiningWorkStep;
use App\Models\TdrProcess;
use App\Models\WoBushingBatch;
use App\Models\WoBushingProcess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Рабочая дата старта шага — machining_work_steps.date_start (шаг 1).
 * Отдельное поле date_send на процессах не нужно: «отправка» = tdr_processes / wo_bushing *.date_start.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machining_work_steps', function (Blueprint $table) {
            if (! Schema::hasColumn('machining_work_steps', 'date_start')) {
                $table->date('date_start')->nullable()->after('machinist_user_id');
            }
        });

        foreach (['tdr_processes', 'wo_bushing_batches', 'wo_bushing_processes'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                if (Schema::hasColumn($tbl, 'date_send')) {
                    $table->dropColumn('date_send');
                }
            });
        }

        $this->backfillStep1WorkStartFromParentSendDate();
    }

    public function down(): void
    {
        Schema::table('machining_work_steps', function (Blueprint $table) {
            if (Schema::hasColumn('machining_work_steps', 'date_start')) {
                $table->dropColumn('date_start');
            }
        });
    }

    private function backfillStep1WorkStartFromParentSendDate(): void
    {
        MachiningWorkStep::query()->where('step_index', 1)->whereNotNull('tdr_process_id')->each(function (MachiningWorkStep $s): void {
            if ($s->date_start !== null) {
                return;
            }
            $p = TdrProcess::query()->find($s->tdr_process_id);
            if ($p !== null && $p->date_start !== null) {
                $s->date_start = $p->date_start;
                $s->saveQuietly();
            }
        });

        MachiningWorkStep::query()->where('step_index', 1)->whereNotNull('wo_bushing_batch_id')->each(function (MachiningWorkStep $s): void {
            if ($s->date_start !== null) {
                return;
            }
            $p = WoBushingBatch::query()->find($s->wo_bushing_batch_id);
            if ($p !== null && $p->date_start !== null) {
                $s->date_start = $p->date_start;
                $s->saveQuietly();
            }
        });

        MachiningWorkStep::query()->where('step_index', 1)->whereNotNull('wo_bushing_process_id')->each(function (MachiningWorkStep $s): void {
            if ($s->date_start !== null) {
                return;
            }
            $p = WoBushingProcess::query()->find($s->wo_bushing_process_id);
            if ($p !== null && $p->date_start !== null) {
                $s->date_start = $p->date_start;
                $s->saveQuietly();
            }
        });
    }
};
