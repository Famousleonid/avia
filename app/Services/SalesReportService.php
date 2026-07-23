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
        $reportType = $this->reportType($filters['report_type'] ?? null, $filters);
        $from = $this->parseDate($filters['date_from'] ?? null);
        $to = $this->parseDate($filters['date_to'] ?? null);

        if (! $this->hasRequiredTarget($reportType, $filters)) {
            return $this->emptyReport($reportType, $from, $to, $this->missingTargetWarning($reportType));
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

        usort($rows, static function (array $a, array $b) use ($reportType): int {
            if ($reportType === 'component') {
                return [$a['aircraft_type'], $a['company'], $a['wo_number']]
                    <=> [$b['aircraft_type'], $b['company'], $b['wo_number']];
            }

            return [$a['company'], $a['wo_number']] <=> [$b['company'], $b['wo_number']];
        });

        $meta = $this->reportMeta($reportType);

        return [
            'report_type' => $reportType,
            'title' => $meta['title'],
            'note' => $meta['note'],
            'period_label' => $this->periodLabel($from, $to),
            'rows' => $rows,
            'total' => $total,
            'warning' => null,
        ];
    }

    public function emptyReport(string $reportType = 'customer', ?CarbonInterface $from = null, ?CarbonInterface $to = null, ?string $warning = null): array
    {
        $reportType = $this->reportType($reportType);
        $meta = $this->reportMeta($reportType);

        return [
            'report_type' => $reportType,
            'title' => $meta['title'],
            'note' => $meta['note'],
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
            ->when($reportType === 'aircraft', function ($query) use ($filters): void {
                // multi-plane CMM: match by ANY of the manual's planes
                $query->whereHas('unit.manual', function ($manual) use ($filters): void {
                    $planeId = (int) $filters['plane_id'];
                    $manual->where(function ($q) use ($planeId): void {
                        $q->whereHas('planes', fn ($p) => $p->where('planes.id', $planeId))
                          ->orWhere('planes_id', $planeId); // manuals created outside the sync path
                    });
                });
            })
            ->when($reportType === 'component', function ($query) use ($filters): void {
                // One component family is represented by one CMM/manual and may
                // contain several approved P/N variants.
                $query->whereHas('unit', function ($unit) use ($filters): void {
                    $unit->where('manual_id', (int) $filters['manual_id']);
                });
            })
            ->orderBy('number')
            ->limit(2000)
            ->get();
    }

    private function hasRequiredTarget(string $reportType, array $filters): bool
    {
        if ($reportType === 'aircraft') {
            return (int) ($filters['plane_id'] ?? 0) > 0;
        }

        if ($reportType === 'component') {
            return (int) ($filters['manual_id'] ?? 0) > 0;
        }

        return (int) ($filters['customer_id'] ?? 0) > 0;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function reportType(mixed $value, array $filters = []): string
    {
        if ($value === 'aircraft') {
            return 'aircraft';
        }

        if ($value === 'component') {
            // Backward compatibility for old links where "component" meant
            // A/C Type and the selected target was stored in plane_id.
            if ((int) ($filters['manual_id'] ?? 0) === 0 && (int) ($filters['plane_id'] ?? 0) > 0) {
                return 'aircraft';
            }

            return 'component';
        }

        return 'customer';
    }

    /**
     * @return array{title: string, note: string}
     */
    private function reportMeta(string $reportType): array
    {
        return match ($reportType) {
            'aircraft' => [
                'title' => 'Sales Report - A/C Type',
                'note' => 'Report based on one A/C type',
            ],
            'component' => [
                'title' => 'Sales Report - Components',
                'note' => 'Report based on one component',
            ],
            default => [
                'title' => 'Sales Report - Customer',
                'note' => 'Report based on one customer',
            ],
        };
    }

    private function missingTargetWarning(string $reportType): string
    {
        return match ($reportType) {
            'aircraft' => 'Select an A/C type to build the report.',
            'component' => 'Select a component to build the report.',
            default => 'Select a customer to build the report.',
        };
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
