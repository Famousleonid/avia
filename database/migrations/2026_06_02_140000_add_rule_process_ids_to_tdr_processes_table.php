<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            // Which point-rule processes (ManualParameterRuleProcess ids) fed this
            // TDR process group — used to find their document templates for generation.
            $table->json('rule_process_ids')->nullable()->after('processes');
        });
    }

    public function down(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            $table->dropColumn('rule_process_ids');
        });
    }
};
