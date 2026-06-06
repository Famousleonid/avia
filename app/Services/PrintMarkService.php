<?php

namespace App\Services;

use App\Models\PrintMark;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

class PrintMarkService
{
    private const PUBLIC_BASE_URL = 'https://aviatechnik.ca/p/';
    private const TOKEN_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    private const TOKEN_LENGTH = 12;

    public function create(array $data): PrintMark
    {
        $payload = [
            'workorder_id' => $data['workorder_id'] ?? null,
            'workorder_number' => $this->normalizeWorkorderNumber((string) ($data['workorder_number'] ?? '')),
            'form_name' => trim((string) ($data['form_name'] ?? 'Printed Form')),
            'printed_by_user_id' => $data['printed_by_user_id'] ?? null,
            'printed_by_name' => trim((string) ($data['printed_by_name'] ?? 'system')),
            'printed_at' => $this->normalizePrintedAt($data['printed_at'] ?? now()),
        ];

        for ($attempt = 0; $attempt < 10; $attempt++) {
            try {
                return PrintMark::query()->create($payload + [
                    'token' => $this->newToken(),
                ]);
            } catch (QueryException $exception) {
                if (! str_contains(strtolower($exception->getMessage()), 'unique')) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Could not create a unique print mark token.');
    }

    public function publicUrl(PrintMark $printMark): string
    {
        return self::PUBLIC_BASE_URL . $printMark->token;
    }

    private function newToken(): string
    {
        $token = '';
        $max = strlen(self::TOKEN_ALPHABET) - 1;

        for ($i = 0; $i < self::TOKEN_LENGTH; $i++) {
            $token .= self::TOKEN_ALPHABET[random_int(0, $max)];
        }

        return $token;
    }

    private function normalizeWorkorderNumber(string $workorderNumber): string
    {
        $digits = preg_replace('/\D+/', '', $workorderNumber);

        return $digits !== '' ? 'W' . $digits : strtoupper($workorderNumber);
    }

    private function normalizePrintedAt(mixed $printedAt): CarbonInterface
    {
        if ($printedAt instanceof CarbonInterface) {
            return $printedAt;
        }

        return Carbon::parse($printedAt);
    }
}
