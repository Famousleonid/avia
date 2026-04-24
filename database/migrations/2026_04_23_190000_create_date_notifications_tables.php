<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('date_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('run_month');
            $table->unsignedTinyInteger('run_day');
            $table->boolean('enabled')->default(true);
            $table->string('title')->nullable();
            $table->text('message');
            $table->boolean('respect_user_preferences')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['enabled', 'run_month', 'run_day'], 'date_notifications_schedule_idx');
        });

        Schema::create('date_notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('date_notification_id')->constrained('date_notifications')->cascadeOnDelete();
            $table->string('recipient_type', 20);
            $table->string('recipient_value');
            $table->timestamps();

            $table->unique(
                ['date_notification_id', 'recipient_type', 'recipient_value'],
                'date_notification_recipient_unique'
            );
        });

        Schema::create('date_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('date_notification_id')->constrained('date_notifications')->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('sent_on');
            $table->timestamps();

            $table->unique(
                ['date_notification_id', 'recipient_user_id', 'sent_on'],
                'date_notification_sent_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('date_notification_logs');
        Schema::dropIfExists('date_notification_recipients');
        Schema::dropIfExists('date_notifications');
    }
};
