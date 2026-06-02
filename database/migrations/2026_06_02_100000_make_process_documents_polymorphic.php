<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_documents', function (Blueprint $table) {
            // Documents can belong to a process of ANY phase:
            //   App\Models\ManualParameterRuleProcess   (Main, point rules)
            //   App\Models\MasterRulePhaseRuleProcess    (Start / Finish, part plan)
            $table->dropForeign(['rule_process_id']);
            $table->dropColumn('rule_process_id');
            $table->string('documentable_type')->nullable()->after('id');
            $table->unsignedBigInteger('documentable_id')->nullable()->after('documentable_type');
            $table->index(['documentable_type', 'documentable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('process_documents', function (Blueprint $table) {
            $table->dropIndex(['documentable_type', 'documentable_id']);
            $table->dropColumn(['documentable_type', 'documentable_id']);
            $table->foreignId('rule_process_id')->nullable()
                  ->constrained('manual_parameter_rule_processes')->cascadeOnDelete();
        });
    }
};
