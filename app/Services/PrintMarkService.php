<?php

namespace App\Services;

use App\Models\PrintMark;
use App\Models\TdrProcess;
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
            'requirement_warnings' => $this->normalizeRequirementWarnings($data['requirement_warnings'] ?? []),
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

    /**
     * Return both the warning snapshot stored with the QR mark and any currently
     * missing FIG/ZONE requirements elsewhere in the same work order.
     *
     * The live lookup keeps older QR marks useful: those rows were created before
     * requirement_warnings was stored in print_marks, while the process sheet can
     * already show its print-only asterisk from tdr_processes.
     */
    public function requirementWarnings(PrintMark $printMark): array
    {
        $warnings = $this->normalizeRequirementWarnings($printMark->requirement_warnings ?? []);

        if ($printMark->workorder_id === null) {
            return $warnings;
        }

        $liveWarnings = TdrProcess::query()
            ->whereHas('tdr', fn ($query) => $query->where('workorder_id', $printMark->workorder_id))
            ->where(function ($query): void {
                $query->where('requires_fig', true)
                    ->orWhere('requires_zone', true);
            })
            ->where(function ($query): void {
                $query->whereNull('ignore_row')
                    ->orWhere('ignore_row', false);
            })
            ->get(['description', 'requires_fig', 'requires_zone'])
            ->flatMap(fn (TdrProcess $tdrProcess) => $tdrProcess->missingDescriptionRequirements())
            ->all();

        return $this->normalizeRequirementWarnings(array_merge($warnings, $liveWarnings));
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

    private function normalizeRequirementWarnings(mixed $warnings): array
    {
        $allowed = ['FIG', 'ZONE'];
        $normalized = array_map(
            static fn ($warning) => strtoupper(trim((string) $warning)),
            is_array($warnings) ? $warnings : []
        );

        return array_values(array_intersect($allowed, array_unique($normalized)));
    }

    private function normalizePrintedAt(mixed $printedAt): CarbonInterface
    {
        if ($printedAt instanceof CarbonInterface) {
            return $printedAt;
        }

        return Carbon::parse($printedAt);
    }
}
