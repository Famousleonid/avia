<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TempFileExport implements WithMultipleSheets
{
    protected $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function sheets(): array
    {
        $spreadsheet = IOFactory::load($this->filePath);
        $sheets = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheets[] = new SheetExport($sheet);
        }

        return $sheets;
    }
}

class SheetExport implements WithCustomStartCell
{
    protected $sheet;

    public function __construct($sheet)
    {
        $this->sheet = $sheet;
    }

    public function startCell(): string
    {
        return 'A1';
    }

    public function array(): array
    {
        return $this->sheet->toArray();
    }
}
