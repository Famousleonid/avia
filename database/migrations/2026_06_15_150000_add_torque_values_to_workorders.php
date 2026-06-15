<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-WO torque values for the F&C Document's Torque page. The torque inputs
     * are process_document_element marks (type torque_input) placed on the Table
     * 8002 image; the technician fills a value per mark during F&C Doc
     * generation. Stored as a compact JSON map { element_id: value } — no
     * separate table, no structured spec (the limits live on the printed sheet).
     */
    public function up()
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->json('torque_values')->nullable()->after('id');
        });
    }

    public function down()
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->dropColumn('torque_values');
        });
    }
};
