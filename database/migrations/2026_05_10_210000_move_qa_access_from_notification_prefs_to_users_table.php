<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'qa_access')) {
                $table->boolean('qa_access')->default(false)->after('can_manage_locked_manual_parts');
            }
        });

        DB::table('users')
            ->select(['id', 'notification_prefs'])
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $prefs = json_decode((string) ($user->notification_prefs ?? ''), true);
                    if (! is_array($prefs)) {
                        $prefs = [];
                    }

                    $qaAccess = (bool) ($prefs['qa_access'] ?? false);
                    unset($prefs['qa_access']);

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'qa_access' => $qaAccess,
                            'notification_prefs' => $prefs === [] ? null : json_encode($prefs, JSON_UNESCAPED_UNICODE),
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('users')
            ->select(['id', 'qa_access', 'notification_prefs'])
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $prefs = json_decode((string) ($user->notification_prefs ?? ''), true);
                    if (! is_array($prefs)) {
                        $prefs = [];
                    }

                    $prefs['qa_access'] = (bool) $user->qa_access;

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'notification_prefs' => json_encode($prefs, JSON_UNESCAPED_UNICODE),
                        ]);
                }
            });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'qa_access')) {
                $table->dropColumn('qa_access');
            }
        });
    }
};
