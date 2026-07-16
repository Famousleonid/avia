<?php

namespace App\Services;

use App\Models\Workorder;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class SalesReportService
{
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
        $rows = [];
        $total = 0.0;

        foreach ($workorders as $workorder) {
            $woNumber = $this->woNumber($workorder);
            $invoiceDate = $workorder->sales_invoice_date;
            $amount = $workorder->sales_invoice_amount;

            if ($amount === null || ! $this->dateInsideRange($invoiceDate, $from, $to)) {
                continue;
            }

            $total += (float) $amount;

            $rows[] = [
                'company' => (string) ($workorder->customer?->name ?? ''),
                // full plane set of the CMM (multi-plane manuals list every type)
                'aircraft_type' => (string) ($workorder->unit?->manual?->planeTypesLabel() ?? ''),
                'wo_number' => $woNumber,
                'part_number' => (string) ($workorder->unit?->part_number ?? ''),
                'serial_number' => (string) ($workorder->serial_number ?? ''),
                'description' => $this->description($workorder),
                'invoiced_amount' => (float) $amount,
                'date_label' => format_project_date($invoiceDate),
                'invoice_date' => $invoiceDate?->toDateString(),
                'source' => 'workorders.sales_invoice_amount',
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
            'warning' => null,
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
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return Collection<int, Workorder>
     */
    private function workorders(string $reportType, array $filters): Collection
    {
        return Workorder::query()
            ->with(['customer', 'unit.manual.plane', 'unit.manual.planes'])
            ->when($reportType === 'customer', function ($query) use ($filters): void {
                $query->where('customer_id', (int) $filters['customer_id']);
            })
            ->when($reportType === 'component', function ($query) use ($filters): void {
                // multi-plane CMM: match by ANY of the manual's planes
                $query->whereHas('unit.manual', function ($manual) use ($filters): void {
                    $planeId = (int) $filters['plane_id'];
                    $manual->where(function ($q) use ($planeId): void {
                        $q->whereHas('planes', fn ($p) => $p->where('planes.id', $planeId))
                          ->orWhere('planes_id', $planeId); // manuals created outside the sync path
                    });
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
