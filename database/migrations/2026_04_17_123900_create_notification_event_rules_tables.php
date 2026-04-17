<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_event_rules', function (Blueprint $table) {
            $table->id();
            $table->string('event_key');
            $table->string('name')->nullable();
            $table->boolean('enabled')->default(true);
            $table->string('severity', 20)->default('info');
            $table->string('repeat_policy', 30)->default('event_default');
            $table->unsignedInteger('repeat_every_minutes')->nullable();
            $table->string('title_template')->nullable();
            $table->text('message_template')->nullable();
            $table->boolean('respect_user_preferences')->default(true);
            $table->boolean('exclude_actor')->default(true);
            $table->json('conditions')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['event_key', 'enabled']);
        });

        Schema::create('notification_event_rule_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_event_rule_id');
            $table->string('recipient_type', 20);
            $table->string('recipient_value');
            $table->timestamps();

            $table->foreign('notification_event_rule_id', 'notif_rule_rec_rule_fk')
                ->references('id')
                ->on('notification_event_rules')
                ->cascadeOnDelete();
            $table->unique([
                'notification_event_rule_id',
                'recipient_type',
                'recipient_value',
            ], 'notification_rule_recipient_unique');
        });

        Schema::table('event_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('notification_event_rule_id')->nullable()->after('event_key');
            $table->foreign('notification_event_rule_id', 'event_logs_notif_rule_fk')
                ->references('id')
                ->on('notification_event_rules')
                ->nullOnDelete();
            $table->index('notification_event_rule_id');
        });
    }

    public function down(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropForeign('event_logs_notif_rule_fk');
            $table->dropIndex(['notification_event_rule_id']);
            $table->dropColumn('notification_event_rule_id');
        });

        Schema::dropIfExists('notification_event_rule_recipients');
        Schema::dropIfExists('notification_event_rules');
    }
};
