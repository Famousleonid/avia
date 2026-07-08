<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CMM torque range for a torque_input mark (value_source = 'torque').
     * When set, the F&C Document fill page can suggest a realistic wrench
     * setting inside [min, max] (Auto-fill); the tech reviews/edits before
     * saving. Null = manual entry only, as before.
     */
    public function up(): void
    {
        Schema::table('process_document_elements', function (Blueprint $table) {
            $table->decimal('torque_min', 8, 2)->nullable()->after('formula_tol_minus');
            $table->decimal('torque_max', 8, 2)->nullable()->after('torque_min');
        });
    }

    public function down(): void
    {
        Schema::table('process_document_elements', function (Blueprint $table) {
            $table->dropColumn(['torque_min', 'torque_max']);
        });
    }
};
