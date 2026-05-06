<?php
// Простой тест без зависимостей
header('Content-Type: text/plain; charset=utf-8');

echo "=== ТЕСТОВАЯ ДИАГНОСТИКА ===\n\n";

// Проверяем PHP
echo "PHP Version: " . phpversion() . "\n";
echo "Memory limit: " . ini_get('memory_limit') . "\n";
echo "Max execution time: " . ini_get('max_execution_time') . "s\n\n";

// Проверяем пути
echo "=== ПРОВЕРКА ПУТЕЙ ===\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "Parent dir: " . dirname(__DIR__) . "\n";

$configFile = dirname(__DIR__) . '/config.php';
echo "config.php exists: " . (file_exists($configFile) ? 'YES' : 'NO') . "\n";
echo "config.php readable: " . (is_readable($configFile) ? 'YES' : 'NO') . "\n";

$classesDir = dirname(__DIR__) . '/classes';
echo "classes dir exists: " . (file_exists($classesDir) ? 'YES' : 'NO') . "\n";

$parserClass = $classesDir . '/PageParser.php';
echo "PageParser.php exists: " . (file_exists($parserClass) ? 'YES' : 'NO') . "\n\n";

// Проверяем логи
$logDir = dirname(__DIR__) . '/cache/logs';
echo "=== ПРОВЕРКА ЛОГОВ ===\n";
echo "log dir exists: " . (file_exists($logDir) ? 'YES' : 'NO') . "\n";
echo "log dir writable: " . (is_writable($logDir) ? 'YES' : 'NO') . "\n";

if (file_exists($logDir)) {
    $files = scandir($logDir);
    echo "Log files: " . implode(', ', array_diff($files, ['.', '..'])) . "\n";
}

// Пробуем включить error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "\n=== ПОПЫТКА ПОДКЛЮЧЕНИЯ ===\n";
try {
    require_once $configFile;
    echo "config.php loaded successfully\n";
    
    require_once $parserClass;
    echo "PageParser.php loaded successfully\n";
    
    $parser = new PageParser();
    echo "PageParser instantiated successfully\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== ТЕСТ ЗАВЕРШЕН ===\n";
?>