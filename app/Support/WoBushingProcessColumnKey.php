<?php

namespace App\Support;

use App\Models\Process;

/**
 * Ключ колонки шапки в таблице bushing (NDT, Machining, …), без детализации NDT-1 vs NDT-4.
 */
final class WoBushingProcessColumnKey
{
    public static function fromProcess(?Process $process): string
    {
        if (! $process || ! $process->process_name) {
            return 'other';
        }

        return self::resolve(
            trim((string) $process->process_name->name),
            trim((string) ($process->process ?? ''))
        );
    }

    public static function resolve(string $processName, string $processCode = ''): string
    {
        $haystack = mb_strtolower(trim($processName.' '.$processCode));
        if ($haystack === '') {
            return 'other';
        }
        if (str_contains($haystack, 'machining')) {
            return 'machining';
        }
        if (str_contains($haystack, 'stress') || str_contains($haystack, 'bake')) {
            return 'stress_relief';
        }
        if (str_contains($haystack, 'ndt') || str_contains($haystack, 'eddy current') || str_contains($haystack, 'bni')) {
            return 'ndt';
        }
        if (str_contains($haystack, 'passivation')) {
            return 'passivation';
        }
        if (str_contains($haystack, 'cad ') || str_contains($haystack, 'cad-') || str_contains($haystack, 'cadmium') || str_contains($haystack, 'cad plate')) {
            return 'cad';
        }
        if (str_contains($haystack, 'anodiz')) {
            return 'anodizing';
        }
        if (str_contains($haystack, 'xylan')) {
            return 'xylan';
        }

        return 'other';
    }
}
