<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_wo_estimate_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workorder_id')->constrained('workorders')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->date('estimate_date');
            $table->dateTime('triggered_at')->nullable();
            $table->dateTime('due_at')->index();
            $table->dateTime('sent_at')->nullable()->index();
            $table->json('recipients')->nullable();
            $table->text('mail_error')->nullable();
            $table->timestamps();

            $table->index(['workorder_id', 'sent_at'], 'marketing_wo_estimate_workorder_sent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_wo_estimate_notifications');
    }
};
