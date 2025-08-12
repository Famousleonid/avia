<?php

// Тестовые данные (ваши данные)
$bushData = [
    [
        "qty" => 2, 
        "bushing" => 19, 
        "processes" => [
            "cad" => 8, 
            "ndt" => 7, 
            "xylan" => null, 
            "machining" => 1, 
            "passivation" => null
        ]
    ],
    [
        "qty" => 1, 
        "bushing" => 21, 
        "processes" => [
            "cad" => 8, 
            "ndt" => null, 
            "xylan" => null, 
            "machining" => 1, 
            "passivation" => null
        ]
    ]
];

// Логика группировки (копия из контроллера)
$processGroups = [];

foreach ($bushData as $bushItem) {
    if (isset($bushItem['bushing']) && isset($bushItem['processes'])) {
        $processes = $bushItem['processes'];
        
        // Собираем активные процессы в правильном порядке
        $activeProcesses = [];
        $processOrder = ['Machining', 'NDT', 'Passivation', 'CAD', 'Xylan'];
        
        foreach ($processOrder as $processType) {
            $processKey = strtolower($processType);
            if (!empty($processes[$processKey])) {
                $activeProcesses[] = $processType;
            }
        }
        
        // Сортируем процессы для создания уникального ключа группы
        sort($activeProcesses);
        $groupKey = implode('|', $activeProcesses);
        
        if (!isset($processGroups[$groupKey])) {
            $processGroups[$groupKey] = [
                'processes' => $activeProcesses,
                'components' => [],
                'total_qty' => 0,
                'process_numbers' => []
            ];
            
            // Рассчитываем номера процессов для этой группы
            $processNumber = 1;
            foreach ($activeProcesses as $process) {
                $processGroups[$groupKey]['process_numbers'][$process] = $processNumber;
                $processNumber++;
            }
        }
        
        $processGroups[$groupKey]['components'][] = [
            'component' => $bushItem['bushing'],
            'qty' => $bushItem['qty'] ?? 1
        ];
        $processGroups[$groupKey]['total_qty'] += $bushItem['qty'] ?? 1;
    }
}

// Сортируем группы по порядку процессов
uasort($processGroups, function($a, $b) {
    $processOrder = ['Machining', 'NDT', 'Passivation', 'CAD', 'Xylan'];
    
    // Сравниваем по первому процессу в группе
    $aFirst = $a['processes'][0] ?? '';
    $bFirst = $b['processes'][0] ?? '';
    
    $aIndex = array_search($aFirst, $processOrder);
    $bIndex = array_search($bFirst, $processOrder);
    
    if ($aIndex === false) $aIndex = 999;
    if ($bIndex === false) $bIndex = 999;
    
    return $aIndex - $bIndex;
});

// Выводим результат
echo "=== РЕЗУЛЬТАТ ГРУППИРОВКИ ===\n";
foreach ($processGroups as $groupKey => $group) {
    echo "Группа: " . $groupKey . "\n";
    echo "  Процессы: " . implode(', ', $group['processes']) . "\n";
    echo "  Номера процессов: ";
    foreach ($group['process_numbers'] as $process => $number) {
        echo "$process=$number ";
    }
    echo "\n";
    echo "  Компоненты: ";
    foreach ($group['components'] as $comp) {
        echo "ID{$comp['component']}(qty:{$comp['qty']}) ";
    }
    echo "\n";
    echo "  Общее количество: {$group['total_qty']}\n";
    echo "\n";
}

echo "=== ОЖИДАЕМЫЙ РЕЗУЛЬТАТ ===\n";
echo "Группа 1: machining - 1; ndt - 2; cad - 3 (QTY = 2)\n";
echo "Группа 2: machining - 1; cad - 2 (QTY = 1)\n";
?>
