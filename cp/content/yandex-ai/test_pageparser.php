<?php
// Тест загрузки PageParser

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/PageParser.php';

echo "<h1>Тест PageParser</h1>";

try {
    echo "Создаем экземпляр PageParser...<br>";
    $parser = new PageParser();
    echo "✅ PageParser создан успешно!<br>";
    
    echo "Пробуем парсить тестовый URL...<br>";
    $result = $parser->parse('https://ya.ru', ['use_cache' => false]);
    
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
} catch (Error $e) {
    echo "<h2>❌ Ошибка:</h2>";
    echo "<b>" . $e->getMessage() . "</b><br>";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    
} catch (Exception $e) {
    echo "<h2>❌ Исключение:</h2>";
    echo "<b>" . $e->getMessage() . "</b><br>";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "<br>";
}
?>