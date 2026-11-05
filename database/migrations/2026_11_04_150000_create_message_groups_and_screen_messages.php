<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('message_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });
        Schema::create('message_group_user', function (Blueprint $table) {
            $table->foreignId('message_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['message_group_id', 'user_id']);
        });
        Schema::create('screen_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_name');
            $table->text('message');
            $table->timestamps();
        });
        Schema::create('screen_message_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('dismissed_at')->nullable();
            $table->unique(['screen_message_id', 'user_id']);
            $table->index(['user_id', 'dismissed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screen_message_recipients');
        Schema::dropIfExists('screen_messages');
        Schema::dropIfExists('message_group_user');
        Schema::dropIfExists('message_groups');
    }
};
