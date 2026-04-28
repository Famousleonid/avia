<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class VendorTrackingExport implements FromArray, ShouldAutoSize, WithEvents
{
    private const COLUMN_LABELS = [
        'repair_order' => 'RO',
        'type' => 'Type',
        'vendor' => 'Vendor',
        'wo' => 'WO',
        'customer' => 'Customer',
        'ipl' => 'IPL',
        'part_number' => 'Part Number',
        'serial' => 'Serial',
        'process' => 'Process',
        'sent' => 'Sent',
        'returned' => 'Returned',
        'days' => 'Days',
    ];

    private Collection $rows;
    private array $columns;
    private string $title;

    public function __construct(
        Collection $rows,
        array $columns,
        string $title
    ) {
        $this->rows = $rows;
        $this->columns = $columns;
        $this->title = $title;
    }

    public function array(): array
    {
        $rows = [];
        $title = trim($this->title);

        if ($title !== '') {
            $rows[] = [''];
            $rows[] = [];
        }

        $rows[] = array_map(
            fn (string $column) => self::COLUMN_LABELS[$column] ?? $column,
            $this->columns
        );

        foreach ($this->rows as $row) {
            $rows[] = $this->mapRow($row);
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $title = trim($this->title);
                if ($title === '' || count($this->columns) === 0) {
                    return;
                }

                $lastColumnIndex = count($this->columns);
                $lastColumnLetter = $event->sheet->getDelegate()
                    ->getCellByColumnAndRow($lastColumnIndex, 1)
                    ->getColumn();

                $range = 'A1:' . $lastColumnLetter . '1';
                $event->sheet->mergeCells($range);
                $event->sheet->setCellValue('A1', $title);
                $event->sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle($range)->getFont()->setBold(true)->setSize(14);
            },
        ];
    }

    private function mapRow(object $row): array
    {
        $sent = $row->date_start;
        $returned = $row->date_finish;
        $days = $sent ? $sent->diffInDays($returned ?: now()) : null;
        $woNumber = (string) ($row->workorder?->number ?? '');
        $woDisplay = $woNumber !== '' ? trim('w ' . preg_replace('/(\d{3})(?=\d)/', '$1 ', $woNumber)) : '';

        $data = [
            'repair_order' => (string) ($row->repair_order ?? ''),
            'type' => (string) ($row->source ?? ''),
            'vendor' => (string) ($row->vendor?->name ?? ''),
            'wo' => $woDisplay,
            'customer' => (string) ($row->customer?->name ?? ''),
            'ipl' => (string) ($row->ipl_num ?? ''),
            'part_number' => (string) ($row->part_number ?? ''),
            'serial' => (string) ($row->serial ?? ''),
            'process' => (string) ($row->process_name ?? ''),
            'sent' => optional($sent)->format('Y-m-d') ?? '',
            'returned' => optional($returned)->format('Y-m-d') ?? '',
            'days' => $days ?? '',
        ];

        return array_map(
            fn (string $column) => $data[$column] ?? '',
            $this->columns
        );
    }
}
