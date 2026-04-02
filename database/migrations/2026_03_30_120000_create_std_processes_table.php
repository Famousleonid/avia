<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('std_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->constrained()->cascadeOnDelete();
            $table->string('std', 16);
            $table->string('ipl_num', 64);
            $table->string('part_number', 255)->default('');
            $table->text('description')->nullable();
            $table->string('process', 255)->default('1');
            $table->unsignedInteger('qty')->default(1);
            $table->string('manual', 255)->nullable();
            $table->timestamps();

            $table->index(['manual_id', 'std']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('std_processes');
    }
};
