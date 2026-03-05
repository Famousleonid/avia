<?php

// database/migrations/xxxx_xx_xx_create_event_logs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_logs', function (Blueprint $table) {
            $table->id();

            $table->string('event_key');          // напр: tdr_process.overdue_start
            $table->morphs('subject');            // subject_type, subject_id (например TdrProcess)
            $table->unsignedBigInteger('recipient_user_id')->nullable();

            $table->timestamp('first_sent_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->unsignedInteger('sent_count')->default(0);

            // чтобы быстро искать
            $table->index(['event_key', 'subject_type', 'subject_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_logs');
    }
};
