<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->timestamp('temporary_password_expires_at')->nullable()->after('must_change_password');
            $table->timestamp('password_changed_at')->nullable()->after('temporary_password_expires_at');
            $table->unsignedBigInteger('auth_version')->default(1)->after('password_changed_at');
        });

        // Existing passwords may predate the strengthened policy. Every existing
        // account gets one authenticated, one-time password change.
        DB::table('users')->update([
            'must_change_password' => true,
            'temporary_password_expires_at' => null,
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'must_change_password',
                'temporary_password_expires_at',
                'password_changed_at',
                'auth_version',
            ]);
        });
    }
};
