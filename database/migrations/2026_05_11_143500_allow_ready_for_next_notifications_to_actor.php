<?php

use App\Services\ProcessSequenceGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notification_event_rules')
            ->where('event_key', ProcessSequenceGuard::READY_EVENT_KEY)
            ->update(['exclude_actor' => false]);
    }

    public function down(): void
    {
        DB::table('notification_event_rules')
            ->where('event_key', ProcessSequenceGuard::READY_EVENT_KEY)
            ->update(['exclude_actor' => true]);
    }
};
