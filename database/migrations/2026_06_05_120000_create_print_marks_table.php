<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_marks', function (Blueprint $table) {
            $table->id();
            $table->string('token', 16)->unique();
            $table->foreignId('workorder_id')->nullable()->constrained()->nullOnDelete();
            $table->string('workorder_number', 32);
            $table->string('form_name', 160);
            $table->foreignId('printed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('printed_by_name', 160);
            $table->timestamp('printed_at');
            $table->timestamps();

            $table->index(['workorder_number', 'form_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_marks');
    }
};
