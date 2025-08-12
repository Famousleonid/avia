<?php
// Простой скрипт для исправления границ

$file_path = 'resources/views/admin/wo_bushings/specProcessForm.blade.php';
$content = file_get_contents($file_path);

// Заменяем все вхождения border-r на inline стили
$content = str_replace(
    'class="col-2 border-r text-center"',
    'class="col-2 text-center" style="height: 20px; border-right: 1px solid black;"',
    $content
);

// Сохраняем исправленный файл
file_put_contents($file_path, $content);

echo "Границы исправлены!\n";
?> 