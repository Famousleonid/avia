<?php

namespace App\Services;

use App\Models\Component;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Database\Eloquent\Builder;

/**
 * Restricts the permanent App Review accounts to explicitly configured
 * synthetic work orders. This is server-side enforcement; mobile filters are
 * only a convenience and must never be relied upon for isolation.
 */
class MobileReviewAccess
{
    public function isReviewUser(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return array_key_exists(
            mb_strtolower(trim((string) $user->email)),
            $this->accounts()
        );
    }

    /** @return list<int> */
    public function workorderNumbersFor(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $account = $this->accounts()[mb_strtolower(trim((string) $user->email))] ?? [];

        return collect($account['workorder_numbers'] ?? [])
            ->map(static fn ($number): int => (int) $number)
            ->filter(static fn (int $number): bool => $number > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function applyWorkorderScope(Builder $query, ?User $user): Builder
    {
        if (! $this->isReviewUser($user)) {
            return $query;
        }

        return $query->whereIn('number', $this->workorderNumbersFor($user));
    }

    public function canAccessWorkorder(?User $user, Workorder $workorder): bool
    {
        return ! $this->isReviewUser($user)
            || in_array((int) $workorder->number, $this->workorderNumbersFor($user), true);
    }

    public function canAccessComponent(?User $user, Component $component): bool
    {
        if (! $this->isReviewUser($user)) {
            return true;
        }

        $numbers = $this->workorderNumbersFor($user);
        if ($numbers === []) {
            return false;
        }

        return Workorder::withDrafts()
            ->whereIn('number', $numbers)
            ->where(function (Builder $query) use ($component): void {
                $query->whereHas('unit', fn (Builder $unit) => $unit->where('manual_id', $component->manual_id))
                    ->orWhereHas('tdrs', fn (Builder $tdrs) => $tdrs->where('component_id', $component->id));
            })
            ->exists();
    }

    /** @return array<string, array{workorder_numbers: array}> */
    private function accounts(): array
    {
        return collect(config('mobile_review.accounts', []))
            ->mapWithKeys(static function ($account, $email): array {
                return [mb_strtolower(trim((string) $email)) => is_array($account) ? $account : []];
            })
            ->all();
    }
}
