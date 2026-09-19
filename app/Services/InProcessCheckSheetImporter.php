<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

/** Extract the reviewed workbook layout. Never import sample WO identities or stamps. */
class InProcessCheckSheetImporter
{
    public function extract(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $names = array_values(array_filter($reader->listWorksheetNames($path), fn ($name) =>
            preg_replace('/[\s_-]+/u', '', strtoupper(trim($name))) === 'INPROCESSCHECKSHEET'));
        if (count($names) !== 1) {
            throw new RuntimeException('Expected exactly one IN PROCESS CHECK SHEET; found '.count($names).'.');
        }
        $reader->setLoadSheetsOnly($names);
        $reader->setReadDataOnly(false);
        $book = $reader->load($path);
        try {
            $result = $this->extractSheet($book->getSheetByName($names[0]));
            $result['source_file'] = basename($path);
            $result['source_sha256'] = hash_file('sha256', $path);
            return $result;
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function extractSheet(Worksheet $sheet): array
    {
        $issues = [];
        $consumed = [];
        $read = function (string $cell) use ($sheet, &$issues, &$consumed): string {
            $consumed[$cell] = true;
            $value = $sheet->getCell($cell)->getValue();
            if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                $value = $value->getPlainText();
            }
            if ($sheet->getCell($cell)->isFormula()) {
                $issues[] = "Formula in $cell requires review; cached results are not template instructions.";
                return '';
            }
            return trim(str_replace(["\r\n", "\r"], "\n", (string) $value));
        };
        // These are runtime WO/P/N fields, even when Excel has saved sample values.
        $consumed['K3'] = $consumed['E5'] = true;
        $header = [];
        foreach (['title'=>'D2', 'instruction'=>'A7', 'manufacturer'=>'J5', 'description'=>'D6', 'library'=>'J6'] as $key=>$cell) {
            $header[$key] = $read($cell);
        }
        foreach (['J2','A5','H5','A6','H6','A8'] as $cell) {
            $read($cell);
        }
        if (preg_replace('/[^A-Z]/', '', strtoupper($header['title'])) !== 'INPROCESSCHECKSHEET') {
            $issues[] = 'Unrecognized check sheet heading/layout.';
        }
        $rows = [];
        $legend = [];
        foreach ($sheet->getCoordinates() as $cell) {
            $value = trim((string) $sheet->getCell($cell)->getValue());
            if (preg_match('/^A(\d+)$/', $cell, $match) && preg_replace('/\s+/', '', strtoupper($value)) === 'INPROCESSSTAGE') {
                $r = (int) $match[1];
                $read($cell);
                $stage = $read('A'.($r + 2));
                if (! preg_match('/^#?\s*[1-8]?$/', $stage)) {
                    $issues[] = "Invalid stage in A".($r + 2).": $stage";
                }
                $rows[] = [
                    'source_cell' => 'C'.$r,
                    'stage' => trim(ltrim($stage, '#')),
                    'task' => $read('C'.$r),
                    'reference_label' => $read('A'.($r + 5)),
                    'reference' => $read('F'.($r + 5)),
                    'stamp_labels' => [$read('M'.($r - 1)), $read('M'.($r + 2))],
                ];
            }
            if (preg_match('/^INPROCESS\s+STAGE\s+LEGEND$/i', $value)) {
                $read($cell);
            } elseif (preg_match('/^#([1-8])\s+(.+)$/s', $value, $match)) {
                $legend[$match[1]] = $read($cell);
            }
        }
        foreach ($sheet->getCoordinates() as $cell) {
            if (! isset($consumed[$cell]) && trim((string) $sheet->getCell($cell)->getValue()) !== '') {
                $issues[] = "Unmapped populated cell $cell; review layout or completed/sample fields.";
            }
        }
        if (! $rows || ! array_filter($rows, fn ($row) => $row['task'] !== '')) {
            $issues[] = 'No filled in-process tasks found.';
        }
        foreach ($rows as $row) {
            if (($row['task'] !== '' && $row['stage'] === '') || ($row['task'] === '' && $row['stage'] !== '')) {
                $issues[] = 'Incomplete task/stage pair at '.$row['source_cell'];
            }
        }
        if (count($legend) !== 8) {
            $issues[] = 'Expected eight in-process stage legend entries.';
        }
        ksort($legend);
        return [
            'schema_version' => 1,
            'source_sheet' => $sheet->getTitle(),
            'header' => $header,
            'rows' => $rows,
            'legend' => array_values($legend),
            'issues' => array_values(array_unique($issues)),
        ];
    }
}
