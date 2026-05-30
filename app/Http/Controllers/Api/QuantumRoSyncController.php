<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuantumRoLine;
use App\Models\QuantumRoSyncRun;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QuantumRoSyncController extends Controller
{
    private const INCREMENTAL_OVERLAP_MINUTES = 10;

    public function state(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $lastRun = QuantumRoSyncRun::query()
            ->where('status', 'completed')
            ->orderByDesc('id')
            ->first();

        $lastSourceModified = QuantumRoLine::query()->max('source_last_modified');
        $recommendedSince = null;

        if ($lastSourceModified) {
            $recommendedSince = Carbon::parse($lastSourceModified)
                ->subMinutes(self::INCREMENTAL_OVERLAP_MINUTES)
                ->format('Y-m-d H:i:s');
        }

        return response()->json([
            'message' => 'Quantum RO sync state.',
            'state' => [
                'last_completed_run_id' => $lastRun?->id,
                'last_completed_run_at' => $lastRun?->finished_at?->format('Y-m-d H:i:s'),
                'last_source_modified' => $lastSourceModified
                    ? Carbon::parse($lastSourceModified)->format('Y-m-d H:i:s')
                    : null,
                'recommended_since' => $recommendedSince,
                'overlap_minutes' => self::INCREMENTAL_OVERLAP_MINUTES,
                'staged_lines' => QuantumRoLine::query()->count(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'run' => ['nullable', 'array'],
            'run.bridge_id' => ['nullable', 'string', 'max:120'],
            'run.started_at' => ['nullable', 'string', 'max:40'],
            'run.finished_at' => ['nullable', 'string', 'max:40'],
            'run.filters' => ['nullable', 'array'],
            'rows' => ['required', 'array', 'max:10000'],
            'rows.*' => ['required', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid Quantum sync payload.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $rows = $request->input('rows', []);
        $runInput = $request->input('run', []);
        $now = now();

        $stats = DB::transaction(function () use ($rows, $runInput, $now): array {
            $run = QuantumRoSyncRun::create([
                'source' => 'quantum',
                'bridge_id' => $this->cleanString($runInput['bridge_id'] ?? null),
                'status' => 'received',
                'filters' => $runInput['filters'] ?? null,
                'rows_received' => count($rows),
                'started_at' => $this->parseDate($runInput['started_at'] ?? null),
                'finished_at' => $this->parseDate($runInput['finished_at'] ?? null),
            ]);

            $inserted = 0;
            $updated = 0;
            $unchanged = 0;
            $roNumbers = [];

            foreach ($rows as $row) {
                $line = $this->normalizeLine($row);
                $line['last_sync_run_id'] = $run->id;
                $line['last_seen_at'] = $now;
                $line['raw_payload'] = $row;

                if ($line['ro_number']) {
                    $roNumbers[] = $line['ro_number'];
                }

                $existing = QuantumRoLine::query()
                    ->where('source_uid', $line['source_uid'])
                    ->first();

                if (! $existing) {
                    $line['first_seen_at'] = $now;
                    QuantumRoLine::create($line);
                    $inserted++;
                    continue;
                }

                if ($existing->source_hash === $line['source_hash']) {
                    $existing->forceFill([
                        'last_sync_run_id' => $run->id,
                        'last_seen_at' => $now,
                        'raw_payload' => $row,
                    ])->save();
                    $unchanged++;
                    continue;
                }

                $existing->forceFill($line)->save();
                $updated++;
            }

            $run->forceFill([
                'status' => 'completed',
                'rows_inserted' => $inserted,
                'rows_updated' => $updated,
                'rows_unchanged' => $unchanged,
                'finished_at' => $run->finished_at ?? $now,
            ])->save();

            $firstReturnedDates = $this->firstReturnedDates(array_values(array_unique($roNumbers)));

            return [
                'run_id' => $run->id,
                'rows_received' => count($rows),
                'rows_inserted' => $inserted,
                'rows_updated' => $updated,
                'rows_unchanged' => $unchanged,
                'first_returned_dates' => $firstReturnedDates,
            ];
        });

        return response()->json([
            'message' => 'Quantum RO rows staged.',
            'stats' => $stats,
        ]);
    }

    private function authorized(Request $request): bool
    {
        $expectedToken = (string) config('services.quantum_sync.token', '');
        $actualToken = (string) $request->bearerToken();

        return $expectedToken !== ''
            && $actualToken !== ''
            && hash_equals($expectedToken, $actualToken);
    }

    private function normalizeLine(array $row): array
    {
        $normalized = [
            'roh_auto_key' => $this->cleanInt($row['roh_auto_key'] ?? null),
            'rod_auto_key' => $this->cleanInt($row['rod_auto_key'] ?? null),
            'wob_auto_key' => $this->cleanInt($row['wob_auto_key'] ?? null),
            'woo_auto_key' => $this->cleanInt($row['woo_auto_key'] ?? null),
            'pnm_auto_key' => $this->cleanInt($row['pnm_auto_key'] ?? null),
            'ro_number' => $this->cleanString($row['ro_number'] ?? $row['ro'] ?? null),
            'wo_number' => $this->cleanString($row['wo_number'] ?? $row['wo'] ?? null),
            'vendor_name' => $this->cleanString($row['vendor_name'] ?? $row['vendor'] ?? null),
            'pn' => $this->cleanString($row['pn'] ?? null),
            'description' => $this->cleanString($row['description'] ?? $row['desc'] ?? null),
            'class' => $this->cleanString($row['class'] ?? null),
            'entry_date' => $this->parseDate($row['entry_date'] ?? null),
            'out_date' => $this->parseDate($row['out_date'] ?? $row['sent_date'] ?? null),
            'returned_date' => $this->parseDate($row['returned_date'] ?? null),
            'ro_last_modified' => $this->parseDate($row['ro_last_modified'] ?? null),
            'detail_last_modified' => $this->parseDate($row['detail_last_modified'] ?? null),
            'source_last_modified' => $this->parseDate($row['source_last_modified'] ?? null),
            'qty_repair' => $this->cleanDecimal($row['qty_repair'] ?? null),
            'qty_reserved' => $this->cleanDecimal($row['qty_reserved'] ?? null),
            'qty_repaired' => $this->cleanDecimal($row['qty_repaired'] ?? null),
        ];

        $normalized['source_uid'] = $this->sourceUid($row, $normalized);
        $normalized['source_hash'] = hash('sha256', json_encode($this->hashPayload($normalized), JSON_UNESCAPED_UNICODE));

        return $normalized;
    }

    private function sourceUid(array $row, array $normalized): string
    {
        $given = $this->cleanString($row['source_uid'] ?? null);
        if ($given) {
            return $given;
        }

        if ($normalized['rod_auto_key']) {
            return 'rod:' . $normalized['rod_auto_key'];
        }

        return 'fallback:' . hash('sha256', implode('|', [
            $normalized['ro_number'] ?? '',
            $normalized['wo_number'] ?? '',
            $normalized['pn'] ?? '',
            $normalized['description'] ?? '',
            $normalized['class'] ?? '',
        ]));
    }

    private function hashPayload(array $line): array
    {
        unset($line['last_sync_run_id'], $line['raw_payload'], $line['first_seen_at'], $line['last_seen_at']);

        return $line;
    }

    private function firstReturnedDates(array $roNumbers): array
    {
        if ($roNumbers === []) {
            return [];
        }

        return QuantumRoLine::query()
            ->select('ro_number', DB::raw('MIN(returned_date) as first_returned_date'))
            ->whereIn('ro_number', $roNumbers)
            ->whereNotNull('returned_date')
            ->groupBy('ro_number')
            ->pluck('first_returned_date', 'ro_number')
            ->map(fn ($value) => Carbon::parse($value)->format('Y-m-d H:i:s'))
            ->all();
    }

    private function cleanString(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '-') {
            return null;
        }

        return preg_replace('/\s+/', ' ', $value);
    }

    private function cleanInt(mixed $value): ?int
    {
        $value = $this->cleanString($value);

        return $value === null ? null : (int) $value;
    }

    private function cleanDecimal(mixed $value): ?string
    {
        $value = $this->cleanString($value);
        if ($value === null) {
            return null;
        }

        return is_numeric($value) ? (string) $value : null;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        $value = $this->cleanString($value);
        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
