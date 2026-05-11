<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notification_event_rules')
            ->where('event_key', 'tdr_process.ready_for_next')
            ->update([
                'message_template' => 'WO {workorder_no}: send {detail_label} to {process_name}. Previous process {previous_process_name} was returned.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('notification_event_rules')
            ->where('event_key', 'tdr_process.ready_for_next')
            ->update([
                'message_template' => 'WO {workorder_no}: send the detail to {process_name}. Previous process {previous_process_name} was returned.',
                'updated_at' => now(),
            ]);
    }
};
