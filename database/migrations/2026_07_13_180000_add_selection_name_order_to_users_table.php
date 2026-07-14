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
            $table->string('selection_name_order', 10)
                ->default('last_first')
                ->after('name');
        });

        $roleNames = DB::table('roles')->pluck('name', 'id');

        DB::table('users')
            ->select(['id', 'name', 'email', 'role_id'])
            ->orderBy('id')
            ->each(function ($user) use ($roleNames): void {
                if ($this->isStoredFirstLast($user, (string) ($roleNames[$user->role_id] ?? ''))) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['selection_name_order' => 'first_last']);
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('selection_name_order');
        });
    }

    private function isStoredFirstLast(object $user, string $roleName): bool
    {
        $parts = preg_split('/\s+/u', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) < 2) {
            return true;
        }

        $firstToken = $this->normalize($parts[0]);
        $lastToken = $this->normalize($parts[count($parts) - 1]);
        $emailLocal = strtolower(strstr((string) $user->email, '@', true) ?: '');
        $emailParts = array_values(array_filter(preg_split('/[^a-z0-9]+/i', $emailLocal) ?: []));
        $emailFirst = $this->normalize($emailParts[0] ?? '');
        $emailSecond = $this->normalize($emailParts[1] ?? '');

        if ($emailFirst === $firstToken && $emailSecond === $lastToken) {
            return true;
        }

        if ($roleName === 'Manager' && $emailFirst === $firstToken) {
            return true;
        }

        if ($roleName !== 'Admin') {
            return false;
        }

        $forwardName = $this->normalize(implode('', $parts));
        $normalizedEmail = $this->normalize($emailLocal);

        return $normalizedEmail !== ''
            && strlen($normalizedEmail) >= 4
            && str_starts_with($forwardName, $normalizedEmail);
    }

    private function normalize(string $value): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', $value) ?? '');
    }
};
