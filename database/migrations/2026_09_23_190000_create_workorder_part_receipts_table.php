<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workorder_part_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workorder_id')->constrained()->cascadeOnDelete();
            $table->string('row_key', 100);
            $table->string('po_num')->nullable();
            $table->date('received')->nullable();
            $table->timestamps();
            $table->unique(['workorder_id', 'row_key']);
        });
    }
    public function down(): void { Schema::dropIfExists('workorder_part_receipts'); }
};
