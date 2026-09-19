<?php

declare(strict_types=1);

/** Normalize a visually verified IPL quantity, preserving non-numeric tokens in the caller's audit. */
function normalizeIplQuantity(mixed $raw): string
{
    $value = trim((string) ($raw ?? ''));
    if ($value === '' || preg_match('/\p{L}/u', $value) === 1) {
        return '1';
    }
    if (preg_match('/^\d+(?:[.,]\d+)?$/D', $value) === 1) {
        return str_replace(',', '.', $value);
    }

    throw new InvalidArgumentException('Ambiguous IPL quantity: visually review the source.');
}
