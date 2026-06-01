<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_documents', function (Blueprint $table) {
            $table->id();
            // Document belongs to a process inside a POINT repair rule (Main).
            $table->foreignId('rule_process_id')
                  ->constrained('manual_parameter_rule_processes')
                  ->cascadeOnDelete();
            $table->string('doc_type')->default('drawing'); // drawing | manual_page | test_report ...
            $table->string('title')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_documents');
    }
};
