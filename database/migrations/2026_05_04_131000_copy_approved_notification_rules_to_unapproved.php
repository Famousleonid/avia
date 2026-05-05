<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('notification_event_rules')
            || ! Schema::hasTable('notification_event_rule_recipients')
        ) {
            return;
        }

        $approvedRules = DB::table('notification_event_rules')
            ->where('event_key', 'workorder.approved')
            ->get();

        foreach ($approvedRules as $approvedRule) {
            $name = 'Unapproved - ' . ($approvedRule->name ?: 'Workorder approved');

            $exists = DB::table('notification_event_rules')
                ->where('event_key', 'workorder.unapproved')
                ->where('name', $name)
                ->exists();

            if ($exists) {
                continue;
            }

            $newRuleId = DB::table('notification_event_rules')->insertGetId([
                'event_key' => 'workorder.unapproved',
                'name' => $name,
                'enabled' => $approvedRule->enabled,
                'severity' => 'warning',
                'repeat_policy' => $approvedRule->repeat_policy,
                'repeat_every_minutes' => $approvedRule->repeat_every_minutes,
                'title_template' => 'Unapproved',
                'message_template' => 'Workorder {workorder_no} unapproved by {actor_name}.',
                'respect_user_preferences' => $approvedRule->respect_user_preferences,
                'exclude_actor' => $approvedRule->exclude_actor,
                'conditions' => $approvedRule->conditions,
                'created_by' => $approvedRule->created_by,
                'updated_by' => $approvedRule->updated_by,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $recipients = DB::table('notification_event_rule_recipients')
                ->where('notification_event_rule_id', $approvedRule->id)
                ->get();

            foreach ($recipients as $recipient) {
                DB::table('notification_event_rule_recipients')->insert([
                    'notification_event_rule_id' => $newRuleId,
                    'recipient_type' => $recipient->recipient_type,
                    'recipient_value' => $recipient->recipient_value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_event_rules')) {
            return;
        }

        $ruleIds = DB::table('notification_event_rules')
            ->where('event_key', 'workorder.unapproved')
            ->where('name', 'like', 'Unapproved - %')
            ->pluck('id');

        if ($ruleIds->isEmpty()) {
            return;
        }

        DB::table('notification_event_rule_recipients')
            ->whereIn('notification_event_rule_id', $ruleIds)
            ->delete();

        DB::table('notification_event_rules')
            ->whereIn('id', $ruleIds)
            ->delete();
    }
};
