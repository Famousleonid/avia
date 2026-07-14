<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_wo_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workorder_id')->constrained('workorders')->cascadeOnDelete();
            $table->foreignId('media_id')->unique()->constrained('media')->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 40);
            $table->string('display_name');
            $table->text('comment')->nullable();
            $table->uuid('version_group')->index();
            $table->unsignedInteger('version_number')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workorder_id', 'created_at'], 'marketing_wo_files_workorder_created_idx');
        });

        Schema::create('marketing_wo_file_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_wo_file_id')->constrained('marketing_wo_files')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('email_requested')->default(false);
            $table->dateTime('notified_at')->nullable();
            $table->dateTime('email_sent_at')->nullable();
            $table->dateTime('email_next_attempt_at')->nullable()->index();
            $table->unsignedSmallInteger('email_attempts')->default(0);
            $table->text('email_error')->nullable();
            $table->timestamps();

            $table->unique(['marketing_wo_file_id', 'user_id'], 'marketing_wo_file_recipient_unique');
        });

        Schema::create('marketing_wo_file_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_wo_file_id')->constrained('marketing_wo_files')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('read_at');
            $table->timestamps();

            $table->unique(['marketing_wo_file_id', 'user_id'], 'marketing_wo_file_read_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_wo_file_reads');
        Schema::dropIfExists('marketing_wo_file_recipients');
        Schema::dropIfExists('marketing_wo_files');
    }
};
