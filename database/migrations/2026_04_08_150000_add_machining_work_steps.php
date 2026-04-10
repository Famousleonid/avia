<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdr_processes', function (Blueprint $table) {
            if (! Schema::hasColumn('tdr_processes', 'working_steps_count')) {
                $table->unsignedTinyInteger('working_steps_count')->nullable()->after('date_finish');
            }
        });

        Schema::table('wo_bushing_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('wo_bushing_batches', 'working_steps_count')) {
                $table->unsignedTinyInteger('working_steps_count')->nullable()->after('date_finish');
            }
        });

        Schema::table('wo_bushing_processes', function (Blueprint $table) {
            if (! Schema::hasColumn('wo_bushing_processes', 'working_steps_count')) {
                $table->unsignedTinyInteger('working_steps_count')->nullable()->after('date_finish');
            }
        });

        Schema::create('machining_work_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tdr_process_id')->nullable()->constrained('tdr_processes')->cascadeOnDelete();
            $table->foreignId('wo_bushing_batch_id')->nullable()->constrained('wo_bushing_batches')->cascadeOnDelete();
            $table->foreignId('wo_bushing_process_id')->nullable()->constrained('wo_bushing_processes')->cascadeOnDelete();
            $table->unsignedTinyInteger('step_index');
            $table->foreignId('machinist_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_finish')->nullable();
            $table->timestamps();

            $table->index(['tdr_process_id', 'step_index']);
            $table->index(['wo_bushing_batch_id', 'step_index']);
            $table->index(['wo_bushing_process_id', 'step_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machining_work_steps');

        Schema::table('wo_bushing_processes', function (Blueprint $table) {
            if (Schema::hasColumn('wo_bushing_processes', 'working_steps_count')) {
                $table->dropColumn('working_steps_count');
            }
        });

        Schema::table('wo_bushing_batches', function (Blueprint $table) {
            if (Schema::hasColumn('wo_bushing_batches', 'working_steps_count')) {
                $table->dropColumn('working_steps_count');
            }
        });

        Schema::table('tdr_processes', function (Blueprint $table) {
            if (Schema::hasColumn('tdr_processes', 'working_steps_count')) {
                $table->dropColumn('working_steps_count');
            }
        });
    }
};
