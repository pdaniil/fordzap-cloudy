<?php
// Полная диагностика системы
header('Content-Type: text/plain; charset=utf-8');

echo "=== ЭКСТРЕННАЯ ДИАГНОСТИКА ===\n\n";

// 1. Информация о PHP
echo "PHP Version: " . phpversion() . "\n";
echo "User: " . get_current_user() . "\n";
echo "Script: " . __FILE__ . "\n\n";

// 2. Проверка всех необходимых директорий
$baseDir = __DIR__;
$dirs = [
    'cache' => $baseDir . '/cache',
    'cache/responses' => $baseDir . '/cache/responses',
    'cache/pages' => $baseDir . '/cache/pages',
    'cache/logs' => $baseDir . '/cache/logs',
    'classes' => $baseDir . '/classes',
    'classes/parser' => $baseDir . '/classes/parser',
];

echo "=== ПРОВЕРКА ДИРЕКТОРИЙ ===\n";
foreach ($dirs as $name => $path) {
    $exists = file_exists($path) ? '✅' : '❌';
    $writable = is_writable($path) ? '✅' : '❌';
    echo "$name: $path\n";
    echo "  Существует: $exists\n";
    echo "  Запись: $writable\n\n";
}

// 3. Проверка лог-файлов
echo "=== ПРОВЕРКА ЛОГ-ФАЙЛОВ ===\n";
$logDir = $baseDir . '/cache/logs';
if (file_exists($logDir)) {
    $logFiles = [
        'parser_api.log',
        'page_parser.log',
        'blacklist.log',
        'cache_manager.log',
        'cloudflare.log'
    ];
    
    foreach ($logFiles as $file) {
        $path = $logDir . '/' . $file;
        $exists = file_exists($path) ? '✅' : '❌';
        $writable = is_writable($path) ? '✅' : '❌';
        echo "$file: $exists, запись: $writable\n";
    }
}

// 4. Проверка config файлов
echo "\n=== ПРОВЕРКА КОНФИГОВ ===\n";
$configJson = $baseDir . '/config.json';
if (file_exists($configJson)) {
    echo "config.json: ✅\n";
    $content = file_get_contents($configJson);
    $json = json_decode($content, true);
    if ($json) {
        echo "  JSON валиден\n";
        echo "  folder_id: " . ($json['folder_id'] ?? 'не задан') . "\n";
    } else {
        echo "  JSON НЕ валиден! Ошибка: " . json_last_error_msg() . "\n";
    }
} else {
    echo "config.json: ❌ НЕ НАЙДЕН!\n";
}

// 5. Попытка создать лог-файл
echo "\n=== ТЕСТ ЗАПИСИ В ЛОГ ===\n";
$testLog = $logDir . '/test_write.log';
$result = file_put_contents($testLog, date('Y-m-d H:i:s') . " - test\n");
if ($result !== false) {
    echo "✅ Успешно записан тестовый лог\n";
    unlink($testLog);
} else {
    echo "❌ НЕ УДАЛОСЬ записать в лог!\n";
}

// 6. Проверка классов
echo "\n=== ПРОВЕРКА КЛАССОВ ===\n";
$classes = [
    'CacheManager' => $baseDir . '/classes/CacheManager.php',
    'BlacklistManager' => $baseDir . '/classes/BlacklistManager.php',
    'PageParser' => $baseDir . '/classes/PageParser.php',
    'HttpFetcher' => $baseDir . '/classes/parser/HttpFetcher.php',
    'HtmlAnalyzer' => $baseDir . '/classes/parser/HtmlAnalyzer.php',
    'HtmlCleaner' => $baseDir . '/classes/parser/HtmlCleaner.php',
    'ErrorHandler' => $baseDir . '/classes/parser/ErrorHandler.php',
    'CloudflareHandler' => $baseDir . '/classes/parser/CloudflareHandler.php',
];

foreach ($classes as $name => $path) {
    if (file_exists($path)) {
        echo "$name: ✅\n";
    } else {
        echo "$name: ❌ НЕ НАЙДЕН!\n";
    }
}

// 7. Пробная загрузка одного класса
echo "\n=== ТЕСТОВАЯ ЗАГРУЗКА ===\n";
try {
    require_once $baseDir . '/classes/CacheManager.php';
    echo "CacheManager загружен: ✅\n";
} catch (Exception $e) {
    echo "Ошибка загрузки CacheManager: " . $e->getMessage() . "\n";
}

// 8. Права на текущую директорию
echo "\n=== ПРАВА НА ТЕКУЩУЮ ДИРЕКТОРИЮ ===\n";
echo "Текущая директория: " . __DIR__ . "\n";
echo "Права: " . substr(sprintf('%o', fileperms(__DIR__)), -4) . "\n";
echo "Владелец: " . fileowner(__DIR__) . "\n";
echo "Группа: " . filegroup(__DIR__) . "\n";

echo "\n=== ДИАГНОСТИКА ЗАВЕРШЕНА ===\n";
?>