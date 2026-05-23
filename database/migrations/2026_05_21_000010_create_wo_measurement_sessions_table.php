<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One session = one figure inspected for one part (tdr) in one WO.
        // instruction_id comes from workorder but stored here for snapshot clarity
        // when WO instruction changes after session creation.
        Schema::create('wo_measurement_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workorder_id')->constrained()->onDelete('cascade');
            // specific part instance in this WO
            $table->foreignId('tdr_id')->nullable()->constrained('tdrs')->onDelete('set null');
            $table->foreignId('manual_dimension_figure_id')
                ->constrained('manual_dimension_figures')
                ->onDelete('restrict');
            // snapshot of WO instruction at session creation time
            $table->foreignId('instruction_id')->constrained('instructions')->onDelete('restrict');
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->enum('status', ['open', 'finalized'])->default('open');
            $table->timestamp('finalized_at')->nullable();
            $table->unsignedBigInteger('finalized_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('finalized_by')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('wo_measurement_sessions');
        Schema::enableForeignKeyConstraints();
    }
};
