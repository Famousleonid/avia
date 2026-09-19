<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Before this fix, creating/toggling Ignore could populate user_id even
        // though no date had ever been entered. Existing genuine date clears
        // did the opposite and nulled user_id, so remaining users on completely
        // empty rows are legacy non-date actors.
        DB::table('mains')
            ->whereNull('date_start')
            ->whereNull('date_finish')
            ->whereNotNull('user_id')
            ->update(['user_id' => null]);
    }

    public function down(): void
    {
        // The former non-date actor cannot be reconstructed reliably.
    }
};
