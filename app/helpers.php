<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return Auth::check() && Auth::user()->is_admin;
    }
}

if (!function_exists('format_project_date')) {
    function format_project_date($date): ?string
    {
        if ($date === null || trim((string) $date) === '') {
            return null;
        }

        return strtolower(\Carbon\Carbon::parse($date)->format('d.M.Y'));
    }
}

if (!function_exists('parse_project_date')) {
    function parse_project_date($value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return \Carbon\Carbon::createFromFormat('Y-m-d', $raw)->format('Y-m-d');
        }

        if (!preg_match('/^\d{2}\.[a-z]{3}\.\d{4}$/i', $raw)) {
            throw new \InvalidArgumentException('Date format must be dd.mmm.yyyy (example: 10.aug.2026).');
        }

        $normalized = preg_replace_callback(
            '/\.(\w{3})\./',
            static fn (array $m): string => '.' . ucfirst(strtolower((string) $m[1])) . '.',
            $raw
        );

        return \Carbon\Carbon::createFromFormat('d.M.Y', (string) $normalized)->format('Y-m-d');
    }
}
