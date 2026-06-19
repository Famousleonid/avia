<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Explicit link from a transfer to the ORIGIN TDR row (in the receiving WO)
     * it was created from. Together with cloned_tdr_id this makes both ends of a
     * transfer explicit and gives a correct identity for uniqueness:
     * one TDR row → at most one transfer.
     */
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->foreignId('tdr_id')
                ->nullable()
                ->after('id')
                ->constrained('tdrs')
                ->nullOnDelete();
        });

        $this->backfill();

        Schema::table('transfers', function (Blueprint $table) {
            $table->unique('tdr_id');
        });
    }

    /**
     * Best-effort backfill of the origin TDR for legacy transfers.
     * Assigns each resolved TDR to a single transfer so the unique index
     * below can be added safely; ambiguous rows are left NULL (MySQL allows
     * multiple NULLs in a unique index).
     */
    private function backfill(): void
    {
        $usedTdrIds = [];

        $transfers = DB::table('transfers')->orderBy('id')->get();

        foreach ($transfers as $transfer) {
            $candidates = DB::table('tdrs')
                ->where('workorder_id', $transfer->workorder_id)
                ->where(function ($q) use ($transfer) {
                    $q->where('component_id', $transfer->component_id)
                        ->orWhere('order_component_id', $transfer->component_id);
                })
                ->orderBy('id')
                ->pluck('id')
                ->all();

            foreach ($candidates as $tdrId) {
                if (!in_array($tdrId, $usedTdrIds, true)) {
                    DB::table('transfers')->where('id', $transfer->id)->update(['tdr_id' => $tdrId]);
                    $usedTdrIds[] = $tdrId;
                    break;
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropUnique('transfers_tdr_id_unique');
            $table->dropForeign(['tdr_id']);
            $table->dropColumn('tdr_id');
        });
    }
};
