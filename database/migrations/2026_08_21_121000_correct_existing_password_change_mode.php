<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // This migration corrects databases where the preceding migration was
        // already applied before legacy and administrator-issued passwords were
        // distinguished. Existing accounts must change immediately; only a
        // password assigned later by an administrator receives a seven-day term.
        DB::table('users')
            ->where('must_change_password', true)
            ->update(['temporary_password_expires_at' => null]);
    }

    public function down(): void
    {
        // The original expiry cannot be reconstructed safely.
    }
};
