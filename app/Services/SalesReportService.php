<?php

namespace App\Services;

use App\Models\Workorder;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class SalesReportService
{
    public function __construct(private SalesReportQuantumInvoiceProvider $invoiceProvider)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function build(array $filters): array
    {
        $reportType = $this->reportType($filters['report_type'] ?? null);
        $from = $this->parseDate($filters['date_from'] ?? null);
        $to = $this->parseDate($filters['date_to'] ?? null);

        if (! $this->hasRequiredTarget($reportType, $filters)) {
            return $this->emptyReport($reportType, $from, $to, 'Select a customer or A/C type to build the report.');
        }

        $workorders = $this->workorders($reportType, $filters);
        $woNumbers = $workorders
            ->map(fn (Workorder $workorder): string => $this->woNumber($workorder))
            ->values()
            ->all();

        $invoiceResult = $this->invoiceProvider->fetch($woNumbers, $from, $to);
        $invoiceMap = $invoiceResult['items'];
        $quantumAvailable = (bool) $invoiceResult['available'];

        $rows = [];
        $total = 0.0;

        foreach ($workorders as $workorder) {
            $woNumber = $this->woNumber($workorder);
            $invoice = $invoiceMap[$woNumber] ?? null;
            $effectiveDate = $invoice['invoice_date'] ?? $workorder->done_at ?? $workorder->open_at;

            if ($quantumAvailable && $invoice === null) {
                continue;
            }

            if (! $quantumAvailable && ! $this->dateInsideRange($effectiveDate, $from, $to)) {
                continue;
            }

            $amount = $invoice['amount'] ?? null;
            if ($amount !== null) {
                $total += (float) $amount;
            }

            $rows[] = [
                'company' => (string) ($workorder->customer?->name ?? ''),
                'aircraft_type' => (string) ($workorder->unit?->manual?->plane?->type ?? ''),
                'wo_number' => $woNumber,
                'part_number' => (string) ($workorder->unit?->part_number ?? ''),
                'serial_number' => (string) ($workorder->serial_number ?? ''),
                'description' => $this->description($workorder),
                'invoiced_amount' => $amount,
                'date_label' => $this->periodLabel($from, $to),
                'invoice_date' => $invoice['invoice_date'] ?? null,
                'invoice_numbers' => $invoice['invoice_numbers'] ?? '',
                'source' => $invoice['source'] ?? '',
            ];
        }

        usort($rows, function (array $a, array $b) use ($reportType): int {
            $groupKey = $reportType === 'component' ? 'aircraft_type' : 'company';

            return [$a[$groupKey], $a['wo_number']] <=> [$b[$groupKey], $b['wo_number']];
        });

        return [
            'report_type' => $reportType,
            'title' => $reportType === 'component' ? 'Sales Report - Components' : 'Sales Report - Customer',
            'note' => $reportType === 'component' ? 'Report based on one component / A/C type' : 'Report based on one customer',
            'period_label' => $this->periodLabel($from, $to),
            'rows' => $rows,
            'total' => $total,
            'warning' => $invoiceResult['warning'],
            'quantum_available' => $quantumAvailable,
        ];
    }

    public function emptyReport(string $reportType = 'customer', ?CarbonInterface $from = null, ?CarbonInterface $to = null, ?string $warning = null): array
    {
        $reportType = $this->reportType($reportType);

        return [
            'report_type' => $reportType,
            'title' => $reportType === 'component' ? 'Sales Report - Components' : 'Sales Report - Customer',
            'note' => $reportType === 'component' ? 'Report based on one component / A/C type' : 'Report based on one customer',
            'period_label' => $this->periodLabel($from, $to),
            'rows' => [],
            'total' => 0.0,
            'warning' => $warning,
            'quantum_available' => false,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return Collection<int, Workorder>
     */
    private function workorders(string $reportType, array $filters): Collection
    {
        return Workorder::query()
            ->with(['customer', 'unit.manual.plane'])
            ->when($reportType === 'customer', function ($query) use ($filters): void {
                $query->where('customer_id', (int) $filters['customer_id']);
            })
            ->when($reportType === 'component', function ($query) use ($filters): void {
                $query->whereHas('unit.manual', function ($manual) use ($filters): void {
                    $manual->where('planes_id', (int) $filters['plane_id']);
                });
            })
            ->orderBy('number')
            ->limit(2000)
            ->get();
    }

    private function hasRequiredTarget(string $reportType, array $filters): bool
    {
        if ($reportType === 'component') {
            return (int) ($filters['plane_id'] ?? 0) > 0;
        }

        return (int) ($filters['customer_id'] ?? 0) > 0;
    }

    private function reportType(mixed $value): string
    {
        return $value === 'component' ? 'component' : 'customer';
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        return CarbonImmutable::parse(parse_project_date($raw))->startOfDay();
    }

    private function dateInsideRange(mixed $date, ?CarbonInterface $from, ?CarbonInterface $to): bool
    {
        if ($date === null) {
            return $from === null && $to === null;
        }

        $value = CarbonImmutable::parse($date)->startOfDay();

        if ($from !== null && $value->lt($from)) {
            return false;
        }

        if ($to !== null && $value->gt($to)) {
            return false;
        }

        return true;
    }

    private function description(Workorder $workorder): string
    {
        foreach ([$workorder->description, $workorder->unit?->description, $workorder->unit?->name] as $value) {
            $text = trim((string) $value);
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    private function woNumber(Workorder $workorder): string
    {
        $number = strtoupper(trim((string) $workorder->number));

        return str_starts_with($number, 'W') ? $number : 'W' . $number;
    }

    private function periodLabel(?CarbonInterface $from, ?CarbonInterface $to): string
    {
        if ($from === null && $to === null) {
            return 'All dates';
        }

        if ($from !== null && $to !== null) {
            return 'from ' . format_project_date($from) . ' till ' . format_project_date($to);
        }

        if ($from !== null) {
            return 'from ' . format_project_date($from);
        }

        return 'till ' . format_project_date($to);
    }
}
