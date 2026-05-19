<?php

namespace App\Services;

use App\Models\ManualIplBranchRule;
use App\Models\Unit;
use Illuminate\Support\Collection;

class ManualIplBranchRuleResolver
{
    /**
     * @var array<int, \Illuminate\Support\Collection<int, ManualIplBranchRule>>
     */
    protected array $rulesCache = [];

    public function allowsComponentForUnit(Unit $unit, ?string $iplNum, ?int $manualId = null): bool
    {
        $ipl = $this->normalizeValue($iplNum);
        if ($ipl === '') {
            return true;
        }

        $rule = $this->resolveRuleForUnit($unit, $manualId);
        if (! $rule) {
            return true;
        }

        $includePrefix = $this->normalizeValue($rule->include_prefix);
        $excludePrefix = $this->normalizeValue($rule->exclude_prefix);

        if ($excludePrefix !== '' && str_starts_with($ipl, $excludePrefix)) {
            return false;
        }

        if ($includePrefix !== '' && str_starts_with($ipl, $includePrefix)) {
            return true;
        }

        return true;
    }

    public function resolveRuleForUnit(Unit $unit, ?int $manualId = null): ?ManualIplBranchRule
    {
        $manualId = (int) ($manualId ?: $unit->manual_id);
        if ($manualId <= 0) {
            return null;
        }

        $unitPartNumber = $this->normalizeValue($unit->part_number);
        if ($unitPartNumber === '') {
            return null;
        }

        $rules = $this->rulesForManual($manualId);
        if ($rules->isEmpty()) {
            return null;
        }

        /** @var ManualIplBranchRule|null $matched */
        $matched = $rules
            ->filter(function (ManualIplBranchRule $rule) use ($unitPartNumber): bool {
                if ($rule->is_default) {
                    return false;
                }

                $match = $this->normalizeValue($rule->unit_match_value);

                return $match !== '' && str_starts_with($unitPartNumber, $match);
            })
            ->sortByDesc(function (ManualIplBranchRule $rule): int {
                return strlen($this->normalizeValue($rule->unit_match_value));
            })
            ->first();

        if ($matched) {
            return $matched;
        }

        /** @var ManualIplBranchRule|null $defaultRule */
        $defaultRule = $rules->first(fn (ManualIplBranchRule $rule): bool => $rule->is_default);

        return $defaultRule;
    }

    public function resolveExactRuleForUnit(Unit $unit, ?int $manualId = null): ?ManualIplBranchRule
    {
        $manualId = (int) ($manualId ?: $unit->manual_id);
        if ($manualId <= 0) {
            return null;
        }

        $unitPartNumber = $this->normalizeValue($unit->part_number);
        if ($unitPartNumber === '') {
            return null;
        }

        /** @var ManualIplBranchRule|null $matched */
        $matched = $this->rulesForManual($manualId)
            ->filter(function (ManualIplBranchRule $rule) use ($unitPartNumber): bool {
                if ($rule->is_default) {
                    return false;
                }

                $match = $this->normalizeValue($rule->unit_match_value);

                return $match !== '' && str_starts_with($unitPartNumber, $match);
            })
            ->sortByDesc(function (ManualIplBranchRule $rule): int {
                return strlen($this->normalizeValue($rule->unit_match_value));
            })
            ->first();

        return $matched;
    }

    public function resolveDefaultRuleForManual(int $manualId): ?ManualIplBranchRule
    {
        if ($manualId <= 0) {
            return null;
        }

        /** @var ManualIplBranchRule|null $defaultRule */
        $defaultRule = $this->rulesForManual($manualId)
            ->first(fn (ManualIplBranchRule $rule): bool => $rule->is_default);

        return $defaultRule;
    }

    public function rulesForManual(int $manualId): Collection
    {
        if (! array_key_exists($manualId, $this->rulesCache)) {
            $this->rulesCache[$manualId] = ManualIplBranchRule::query()
                ->where('manual_id', $manualId)
                ->orderByDesc('is_default')
                ->orderBy('unit_match_value')
                ->get();
        }

        return $this->rulesCache[$manualId];
    }

    protected function normalizeValue(?string $value): string
    {
        return strtoupper(trim((string) $value));
    }
}
