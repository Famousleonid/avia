<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_rule_phase_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_rule_id')
                  ->constrained('master_rules')
                  ->cascadeOnDelete();
            $table->string('phase', 20);          // 'start' | 'finish' (extensible)
            $table->string('name')->nullable();
            $table->json('condition')->nullable(); // optional trigger (process-in-main / point-fail / defect)
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('master_rule_phase_rules');
    }
};
