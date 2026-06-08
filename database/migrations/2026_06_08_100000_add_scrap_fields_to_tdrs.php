<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B2 Scrap & Order New — infrastructure only.
 *
 * result_status  — outcome of this TDR row ('scrapped' | null = normal)
 * scrap_reason   — free-text reason entered by the technician / QA
 * replaced_by_tdr_id — FK to the new "Order New" TDR created to replace
 *                      the scrapped component (nullable; set when the new
 *                      TDR is created after scrap decision)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdrs', function (Blueprint $table) {
            $table->string('result_status')->nullable()->after('use_process_forms');
            $table->text('scrap_reason')->nullable()->after('result_status');
            $table->unsignedBigInteger('replaced_by_tdr_id')->nullable()->after('scrap_reason');

            $table->foreign('replaced_by_tdr_id')
                ->references('id')->on('tdrs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tdrs', function (Blueprint $table) {
            $table->dropForeign(['replaced_by_tdr_id']);
            $table->dropColumn(['result_status', 'scrap_reason', 'replaced_by_tdr_id']);
        });
    }
};
