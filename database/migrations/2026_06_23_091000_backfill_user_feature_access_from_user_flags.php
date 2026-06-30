<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [];

        $users = DB::table('users')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->whereNull('users.deleted_at')
            ->get([
                'users.id',
                'users.is_admin',
                'users.can_manage_locked_manual_processes',
                'users.can_manage_locked_manual_parts',
                'users.qa_access',
                'users.ec_access',
                'users.can_sign_certificates',
                'users.notification_prefs',
                'roles.name as role_name',
            ]);

        foreach ($users as $user) {
            $featureKeys = [];
            $roleName = (string) ($user->role_name ?? '');
            $isSystemAdmin = (bool) $user->is_admin && $roleName === 'Admin';

            if ($isSystemAdmin || (in_array($roleName, ['Admin', 'Manager'], true) && (bool) $user->qa_access)) {
                $featureKeys[] = 'quality_assurance';
            }

            if ((bool) $user->is_admin || (bool) $user->ec_access) {
                $featureKeys[] = 'ec';
            }

            if (in_array($roleName, ['Admin', 'Manager'], true)) {
                $featureKeys[] = 'vendor_tracking';
            }

            if ((bool) $user->can_sign_certificates) {
                $featureKeys[] = 'certificates.sign';
            }

            if ($this->hasManualsFullAccess($user->notification_prefs)) {
                $featureKeys[] = 'manuals.full';
            }

            if ((bool) $user->can_manage_locked_manual_processes) {
                $featureKeys[] = 'manuals.locked_processes';
            }

            if ((bool) $user->can_manage_locked_manual_parts) {
                $featureKeys[] = 'manuals.locked_parts';
            }

            foreach (array_unique($featureKeys) as $featureKey) {
                $rows[] = [
                    'user_id' => (int) $user->id,
                    'feature_key' => $featureKey,
                    'granted_by_user_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('user_feature_access')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        // Intentionally keep access rows: revoking permissions during rollback is destructive.
    }

    private function hasManualsFullAccess(mixed $notificationPrefs): bool
    {
        if (is_string($notificationPrefs) && $notificationPrefs !== '') {
            $notificationPrefs = json_decode($notificationPrefs, true);
        }

        if (! is_array($notificationPrefs)) {
            return false;
        }

        return (bool) data_get($notificationPrefs, 'manuals_full_access', false);
    }
};
