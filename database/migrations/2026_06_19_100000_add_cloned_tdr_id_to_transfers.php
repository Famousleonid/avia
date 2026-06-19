<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Explicit link from a transfer to the TDR row it cloned into the source
     * workorder. Replaces the fragile multi-field guessing in deleteByTdr().
     */
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->foreignId('cloned_tdr_id')
                ->nullable()
                ->after('component_sn')
                ->constrained('tdrs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropForeign(['cloned_tdr_id']);
            $table->dropColumn('cloned_tdr_id');
        });
    }
};
