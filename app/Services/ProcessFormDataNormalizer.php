<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Нормализует данные форм процессов из разных модулей к единому формату
 * для использования в shared partials (NDT, Stress Relief, Other).
 */
class ProcessFormDataNormalizer
{
    /**
     * Нормализация данных для NDT формы (tdr-processes, extra_processes).
     * Возвращает массив строк для отображения в таблице.
     *
     * @param Collection $ndtComponents Коллекция TdrProcess
     * @return array Массив ['ipl_num', 'part_number', 'name', 'process_numbers', 'qty', ...]
     */
    public function normalizeNdtFromTdr(Collection $ndtComponents): array
    {
        $rows = [];
        $index = 1;

        foreach ($ndtComponents as $component) {
            $processNumbers = [substr($component->processName->name, -1)];

            if ($component->plus_process) {
                $plusProcessIds = explode(',', $component->plus_process);
                foreach ($plusProcessIds as $plusProcessId) {
                    $plusProcessName = \App\Models\ProcessName::find($plusProcessId);
                    if ($plusProcessName && strpos($plusProcessName->name, 'NDT-') === 0) {
                        $processNumbers[] = substr($plusProcessName->name, -1);
                    }
                }
            }

            sort($processNumbers);

            $rows[] = [
                'index' => $index++,
                'ipl_num' => $component->tdr->component->ipl_num,
                'part_number' => $component->tdr->component->part_number,
                'serial_number' => $component->tdr->serial_number,
                'name' => $component->tdr->component->name,
                'process_numbers' => implode(' / ', $processNumbers),
                'qty' => $component->tdr->qty,
            ];
        }

        return $rows;
    }

    /**
     * Нормализация данных для NDT формы (wo_bushings).
     * $table_data — массив с ключами: component, process_name, qty, combined_ndt_number.
     *
     * @param array $tableData
     * @return array
     */
    public function normalizeNdtFromWoBushings(array $tableData): array
    {
        $rows = [];
        $index = 1;

        foreach ($tableData as $data) {
            $component = $data['component'] ?? null;
            $processName = $data['process_name'] ?? null;

            $processNumbers = '';
            if (isset($data['combined_ndt_number'])) {
                $processNumbers = $data['combined_ndt_number'];
            } elseif ($processName) {
                if (strpos($processName->name, 'NDT-') === 0) {
                    $processNumbers = substr($processName->name, 4);
                } elseif ($processName->name === 'Eddy Current Test') {
                    $processNumbers = '6';
                } elseif ($processName->name === 'BNI') {
                    $processNumbers = '5';
                } else {
                    $processNumbers = substr($processName->name, -1);
                }
            }

            $rows[] = [
                'index' => $index++,
                'ipl_num' => $component->ipl_num ?? '',
                'part_number' => $component->part_number ?? '',
                'serial_number' => null,
                'name' => $component->name ?? '',
                'process_numbers' => $processNumbers,
                'qty' => $data['qty'] ?? 1,
            ];
        }

        return $rows;
    }
}
