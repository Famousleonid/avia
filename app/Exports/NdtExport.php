<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class NdtExport implements WithEvents, WithDrawings
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
        $drawing->setHeight(37);
        $drawing->setCoordinates('A1');

        return $drawing;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridlines(false);
                $sheet->setTitle('NDT');
                $sheet->getStyle('A1:AZ100')->getFont()->setName('Times New Roman')->setSize(12);

                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageSetup()->setPrintArea('A1:AF41');

                /*$sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);*/
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);

                $sheet->getPageMargins()->setTop(0.23);
                $sheet->getPageMargins()->setRight(0.23);
                $sheet->getPageMargins()->setLeft(0.23);
                $sheet->getPageMargins()->setBottom(0.23);

                $sheet->getSheetView()->setView(\PhpOffice\PhpSpreadsheet\Worksheet\SheetView::SHEETVIEW_PAGE_BREAK_PREVIEW);
                $sheet->getSheetView()->setZoomScale(100); // Здесь 100 означает 100% масштаб

                $columns = [
                    'A' => 5, 'B' => 5, 'C' => 3, 'D' => 3, 'E' => 4, 'F' => 3, 'G' => 4, 'H' => 3, 'I' => 4, 'J' => 3,
                    'K' => 3, 'L' => 4, 'M' => 5, 'N' => 5, 'O' => 5, 'P' => 3, 'Q' => 5, 'R' => 7, 'S' => 3, 'T' => 3,
                    'U' => 3, 'V' => 5, 'W' => 5, 'X' => 3, 'Y' => 3, 'Z' => 2, 'AA' => 2, 'AB' => 2, 'AC' => 2, 'AD' => 3, 'AE' => 5, 'AF' => 6
                ];
                foreach ($columns as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->mergeCells('A2:AF3');
                $sheet->setCellValue('A2', 'NDT PROCESS SHEET');
                $sheet->getStyle('A2')->getFont()->setSize(20)->setBold(true);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

                $this->setCellWithBorder($sheet, 'G5', 'COMPONENT NAME:', 'H5:O5');
                $this->setCellWithBorder($sheet, 'S5', 'DATE:', 'T5:AF5');
                $this->setCellWithBorder($sheet, 'G7', 'PART NUMBER:', 'H7:O7');
                $this->setCellWithBorder($sheet, 'S7', 'RO No.:', 'T7:AF7');
                $this->setCellWithBorder($sheet, 'G9', 'WORK ORDER No:', 'I9:O9');
                $this->setCellWithBorder($sheet, 'S9', 'VENDOR:', 'T9:AF9');
                $this->setCellWithBorder($sheet, 'G11', 'SERIAL No:', 'H11:O11');
                $this->setCellWithBorder($sheet, 'T19', '', 'T19:AF21');
                $sheet->getStyle('T19:AF21')->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);

                $this->setCellWithBorder($sheet, 'A14', '#1', 'B14:K14');
                $this->setCellWithBorder($sheet, 'L14', '#2', 'M14:S14');
                $this->setCellWithBorder($sheet, 'U14', '#3', 'V14:AF14');
                $this->setCellWithBorder($sheet, 'A17', '#4', 'B17:K17');
                $this->setCellWithBorder($sheet, 'L17', '#5', 'M14:S14');
                $this->setCellWithBorder($sheet, 'U17', '#6', 'V14:AF14');
                $this->setCellWithBorder($sheet, 'A20', '#7', 'B20:K20');

                $sheet->setCellValue('A13', 'MAGNETIC PARTICLE AS PER:');
                $sheet->getStyle('A13')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('A13')->getFont()->setSize(12)->setBold(true);

                $sheet->setCellValue('M13', '');
                $sheet->getStyle('M5')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('M5')->getFont()->setSize(12)->setBold(true);

                $sheet->setCellValue('U13', 'LIQUID/FLUID PENETRANT AS PER:');
                $sheet->getStyle('U13')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('U13')->getFont()->setSize(12)->setBold(true);

                $sheet->setCellValue('A16', 'LIQUID/FLUID PENETRANT AS PER:');
                $sheet->getStyle('A16')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('A16')->getFont()->setSize(12)->setBold(true);

                $sheet->setCellValue('M16', 'ULTRASOUND AS PER:');
                $sheet->getStyle('M16')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('M16')->getFont()->setSize(12)->setBold(true);

                $sheet->setCellValue('U16', 'EDDY CURRENT AS PER:');
                $sheet->getStyle('U16')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('U16')->getFont()->setSize(12)->setBold(true);

                $sheet->setCellValue('A19', 'ULTRASOUND AS PER:');
                $sheet->getStyle('A19')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('A19')->getFont()->setSize(12)->setBold(true);

                $sheet->setCellValue('Q20', 'CMM No:');
                $sheet->getStyle('Q20')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('Q20')->getFont()->setSize(12)->setBold(true);


                //---------- Data -------------------------------------------------------------------------------

                $sheet->setCellValue('H5', $this->data['unit_name']);
                $sheet->getStyle('H5')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('H5')->getFont()->setSize(11)->setBold(true);

                $sheet->setCellValue('H7', $this->data['unit_number']);
                $sheet->getStyle('H7')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('H7')->getFont()->setSize(14)->setBold(true);

                $sheet->setCellValue('H9', 'W');
                $sheet->getStyle('H9')->applyFromArray(['borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]]]);
                $sheet->getStyle('H9')->getAlignment()->setHorizontal('left');
                $sheet->setCellValue('I9', $this->data['wo_number']);
                $sheet->getStyle('I9')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('I9')->getFont()->setSize(14)->setBold(true);

                $sheet->setCellValue('H11', $this->data['serial_number']);
                $sheet->getStyle('H11')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('H11')->getFont()->setSize(14)->setBold(true);

                $sheet->setCellValue('T9', $this->data['vendor']);
                $sheet->getStyle('T9')->getAlignment()->setHorizontal('center');

                //-----------------------------------------------------------------------------------------------

                $headerCells = [
                    ['A23:B24', 'ITEM No.'],
                    ['C23:H24', 'PART No.'],
                    ['I23:P24', 'DESCRIPTION'],
                    ['Q23:T24', 'PROCESS No.'],
                    ['U23:V24', 'QTY'],
                    ['W23:Y24', 'ACCEPT'],
                    ['Z23:AF24', 'REJECT'],
                ];

                foreach ($headerCells as [$range, $text]) {
                    $sheet->mergeCells($range);
                    $sheet->setCellValue(explode(':', $range)[0], $text); // Устанавливаем значение только для первой ячейки
                    $sheet->getStyle($range)->getFont()->setBold(true);
                    $sheet->getStyle($range)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
                    $sheet->getStyle($range)->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
                }
                $sheet->getStyle('A23')->getFont()->setBold(true)->setSize(10);

                for ($row = 25; $row <= 41; $row++) {
                    $this->mergeAndBorderRow($sheet, $row);
                }

                $sheet->getStyle('A25:AB41')->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
                $event->sheet->getParent()->getActiveSheet()->setSelectedCell('T5');

                for ($row = 25; $row <= 41; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(27);
                }
                $sheet->getRowDimension(18)->setRowHeight(10);
                $sheet->getRowDimension(22)->setRowHeight(10);
                $sheet->getRowDimension(14)->setRowHeight(30);
                $sheet->getRowDimension(17)->setRowHeight(30);
                $sheet->getRowDimension(20)->setRowHeight(30);
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
        $ranges = ["A{$row}:B{$row}", "C{$row}:H{$row}", "I{$row}:P{$row}", "Q{$row}:T{$row}", "U{$row}:V{$row}", "W{$row}:Y{$row}", "Z{$row}:AF{$row}"];
        foreach ($ranges as $range) {
            $sheet->mergeCells($range);
            $sheet->getStyle($range)->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
        }
    }
}
