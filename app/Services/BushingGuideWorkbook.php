<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

class BushingGuideWorkbook
{
    public function read(string $path): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setLoadSheetsOnly(['Items', 'Bushing']);
        $book = $reader->load($path);
        $sheets = [];
        foreach (['Items', 'Bushing'] as $name) {
            $sheet = $book->getSheetByName($name);
            if (!$sheet) throw new \RuntimeException("Missing sheet: {$name}");
            $lastRow = 1;
            $lastColumn = 1;
            foreach ($sheet->getCellCollection()->getCoordinates() as $address) {
                $cell = $sheet->getCell($address);
                if ($cell->isFormula()) throw new \RuntimeException("Formula is not supported: {$name}!{$address}");
                if ($cell->getValue() !== null && trim((string) $cell->getValue()) !== '') {
                    $lastRow = max($lastRow, $cell->getRow());
                    $lastColumn = max($lastColumn, Coordinate::columnIndexFromString($cell->getColumn()));
                }
            }
            $merges = [];
            $covered = [];
            foreach ($sheet->getMergeCells() as $range) {
                [[$c1, $r1], [$c2, $r2]] = Coordinate::rangeBoundaries($range);
                if ($r1 > $lastRow) continue;
                $lastColumn = max($lastColumn, $c2);
                $merges[$r1][$c1] = ['colspan' => $c2 - $c1 + 1, 'rowspan' => $r2 - $r1 + 1];
                for ($r = $r1; $r <= $r2; $r++) for ($c = $c1; $c <= $c2; $c++) {
                    if ($r !== $r1 || $c !== $c1) $covered[$r][$c] = true;
                }
            }
            if ($lastRow > 500 || $lastColumn > 40) throw new \RuntimeException('Workbook exceeds the supported form size.');
            $widths = [];
            for ($c = 1; $c <= $lastColumn; $c++) {
                $width = $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->getWidth();
                $widths[] = max(16, ($width > 0 ? $width : 10) * 7 + 5);
            }
            $rows = [];
            for ($r = 1; $r <= $lastRow; $r++) {
                $cells = [];
                for ($c = 1; $c <= $lastColumn; $c++) {
                    if (isset($covered[$r][$c])) continue;
                    $address = Coordinate::stringFromColumnIndex($c).$r;
                    $cell = $sheet->getCell($address);
                    $raw = $cell->getValue();
                    $value = $raw instanceof RichText ? $raw->getPlainText() : (string) ($raw ?? '');
                    $style = $cell->getStyle();
                    $font = $style->getFont();
                    $align = $style->getAlignment()->getHorizontal();
                    $css = 'font-size:'.min(18, max(9, $font->getSize())).'pt;';
                    $css .= 'font-weight:'.($font->getBold() ? '700' : '400').';';
                    $css .= 'font-style:'.($font->getItalic() ? 'italic' : 'normal').';';
                    $css .= 'text-align:'.(in_array($align, ['left', 'center', 'right']) ? $align : 'left').';';
                    $color = $font->getColor()->getRGB();
                    if (preg_match('/^[0-9a-f]{6}$/i', $color ?? '')) $css .= "color:#{$color};";
                    if ($style->getFill()->getFillType() === 'solid') {
                        $color = $style->getFill()->getStartColor()->getRGB();
                        if (preg_match('/^[0-9a-f]{6}$/i', $color ?? '')) $css .= "background-color:#{$color};";
                    }
                    foreach (['top', 'right', 'bottom', 'left'] as $edge) {
                        $border = $style->getBorders()->{'get'.ucfirst($edge)}()->getBorderStyle();
                        if ($border && $border !== 'none') $css .= "border-{$edge}:1px solid #333;";
                    }
                    $span = $merges[$r][$c] ?? ['colspan' => 1, 'rowspan' => 1];
                    // Excel lets these unmerged footnotes flow across empty cells.
                    if ($name === 'Bushing' && $r >= 52 && $c === 1) {
                        $span['colspan'] = $lastColumn;
                        for ($skip = 2; $skip <= $lastColumn; $skip++) $covered[$r][$skip] = true;
                    }
                    $cells[] = array_merge(['address' => $address, 'value' => $value, 'style' => $css], $span);
                }
                $rows[] = ['number' => $r, 'cells' => $cells];
            }
            $sheets[] = ['name' => $name, 'widths' => $widths, 'rows' => $rows];
        }
        $book->disconnectWorksheets();
        return ['source_name' => basename($path), 'source_sha256' => hash_file('sha256', $path), 'sheets' => $sheets];
    }
}
