<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class CadExport implements WithEvents, WithDrawings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setPath(public_path('img/icons/avia.png'));
        $drawing->setHeight(28);
        $drawing->setCoordinates('A1');

        return $drawing;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridlines(false);
                $sheet->setTitle('CAD');
                $sheet->getStyle('A1:AZ100')->getFont()->setName('Times New Roman')->setSize(12);

                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageSetup()->setPrintArea('A1:AB35');

                /*                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);*/
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);

                $sheet->getPageMargins()->setTop(0.1);
                $sheet->getPageMargins()->setRight(0.1);
                $sheet->getPageMargins()->setLeft(0.1);
                $sheet->getPageMargins()->setBottom(0.1);

                $sheet->getSheetView()->setView(\PhpOffice\PhpSpreadsheet\Worksheet\SheetView::SHEETVIEW_PAGE_BREAK_PREVIEW);
                $sheet->getSheetView()->setZoomScale(100); // Здесь 100 означает 100% масштаб

                $columns = [
                    'A' => 6, 'B' => 3, 'C' => 3, 'D' => 3, 'E' => 4, 'F' => 3, 'G' => 4, 'H' => 3, 'I' => 4, 'J' => 3,
                    'K' => 3, 'L' => 2, 'M' => 3, 'N' => 3, 'O' => 6, 'P' => 5, 'Q' => 5, 'R' => 3, 'S' => 7, 'T' => 3,
                    'U' => 8, 'V' => 3, 'W' => 5, 'X' => 5, 'Y' => 5, 'Z' => 3, 'AA' => 6, 'AB' => 6
                ];

                foreach ($columns as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->mergeCells('A2:AB3');
                $sheet->setCellValue('A2', 'CADMIUM PLATING PROCESS SHEET');
                $sheet->getStyle('A2')->getFont()->setSize(20)->setBold(true);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

                $this->setCellWithBorder($sheet, 'G6', 'COMPONENT NAME:', 'H6:O6');
                $this->setCellWithBorder($sheet, 'S6', 'DATE:', 'T6:AB6');
                $this->setCellWithBorder($sheet, 'G8', 'PART NUMBER:', 'H8:O8');
                $this->setCellWithBorder($sheet, 'S8', 'RO No.:', 'T8:AB8');
                $this->setCellWithBorder($sheet, 'G10', 'WORK ORDER No:', 'I10:O10');
                $this->setCellWithBorder($sheet, 'S10', 'VENDOR:', 'T10:AB10');
                $this->setCellWithBorder($sheet, 'G12', 'SERIAL No:', 'H12:O12');
               

                //---------- Data -------------------------------------------------------------------------------

                $sheet->setCellValue('H6', $this->data['unit_name']);
                $sheet->getStyle('H6')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('H6')->getFont()->setSize(11)->setBold(true);

                $sheet->setCellValue('H8', $this->data['unit_number']);
                $sheet->getStyle('H8')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('H8')->getFont()->setSize(14)->setBold(true);

                $sheet->setCellValue('H10', 'W');
                $sheet->getStyle('H10')->applyFromArray(['borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]]]);
                $sheet->getStyle('H10')->getAlignment()->setHorizontal('left');
                $sheet->setCellValue('I10', $this->data['wo_number']);
                $sheet->getStyle('I10')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('I10')->getFont()->setSize(14)->setBold(true);

                $sheet->setCellValue('T10', $this->data['vendor']);
                $sheet->getStyle('T10')->getAlignment()->setHorizontal('left');


                $sheet->setCellValue('H12', $this->data['serial_number']);
                $sheet->getStyle('H12')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('H12')->getFont()->setSize(14)->setBold(true);


                //-----------------------------------------------------------------------------------------------


                $sheet->setCellValue('A14', ' Perform the CAD plate as specified under Process No. and in accordance with CMM No.');
                $sheet->getStyle('A14')->getFont()->setSize(11)->setBold(true);

                $headerCells = [
                    ['A16:B17', 'ITEM No.'],
                    ['C16:H17', 'PART No.'],
                    ['I16:P17', 'DESCRIPTION'],
                    ['Q16:U17', 'PROCESS No.'],
                    ['V16:W17', 'QTY'],
                    ['X16:AB17', 'CMM No.']
                ];

                foreach ($headerCells as [$range, $text]) {
                    $sheet->mergeCells($range);
                    $sheet->setCellValue(explode(':', $range)[0], $text); // Устанавливаем значение только для первой ячейки
                    $sheet->getStyle($range)->getFont()->setBold(true);
                    $sheet->getStyle($range)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
                    $sheet->getStyle($range)->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
                }
                $sheet->getStyle('A16')->getFont()->setBold(true)->setSize(10);

                for ($row = 18; $row <= 34; $row++) {
                    $this->mergeAndBorderRow($sheet, $row);
                }
                $sheet->getStyle('A18:AB34')->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);

                $event->sheet->getParent()->getActiveSheet()->setSelectedCell('T6');

                for ($row = 18; $row <= 34; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(30);
                }

            },
        ];
    }

    private function setCellWithBorder($sheet, $cell, $text, $mergeRange, $data = null, $dataCell = null, $dataSize = null)
    {
        $sheet->setCellValue($cell, $text);
        $sheet->getStyle($cell)->getAlignment()->setHorizontal('right');
        $sheet->getStyle($cell)->getFont()->setBold(true);
        $sheet->mergeCells($mergeRange);
        $sheet->getStyle($mergeRange)->applyFromArray(['borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]]]);

        if ($data) {
            $sheet->setCellValue($dataCell, $data);
            $sheet->getStyle($dataCell)->getAlignment()->setHorizontal('left');
            $sheet->getStyle($dataCell)->getFont()->setSize($dataSize)->setBold(true);
        }
    }

    private function mergeAndBorderRow($sheet, $row)
    {
        $ranges = ["A{$row}:B{$row}", "C{$row}:H{$row}", "I{$row}:P{$row}", "Q{$row}:U{$row}", "V{$row}:W{$row}", "X{$row}:AB{$row}"];
        foreach ($ranges as $range) {
            $sheet->mergeCells($range);
            $sheet->getStyle($range)->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
        }
    }
}
