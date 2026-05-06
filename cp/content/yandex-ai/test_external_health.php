<?php
// /fordzap.com/cp/content/yandex-ai/test_external_health.php
ini_set("display_errors", 1);
error_reporting(E_ALL);
require_once $_SERVER["DOCUMENT_ROOT"] . '/cp/content/yandex-ai/config.php';
require_once 'classes/ExternalParserAPI.php';

$api = new ExternalParserAPI();

echo "Проверка /health...\n";
$health = $api->health();
if ($health) {
    echo "✅ API работает: " . json_encode($health, JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "❌ API не отвечает\n";
}

echo "\nПроверка /api/system...\n";
$stats = $api->systemStats();
if ($stats) {
    echo "✅ Системная статистика: " . json_encode($stats, JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "❌ Не удалось получить статистику\n";
}

echo "\nТест парсинга простой страницы...\n";
$result = $api->parseSingle('https://example.com');
if ($result) {
    echo "✅ Успешно:\n";
    echo "  URL: " . $result['url'] . "\n";
    echo "  Title: " . ($result['title'] ?? '') . "\n";
    echo "  Status: " . $result['status'] . "\n";
    echo "  HTML size: " . ($result['size'] ?? 0) . " bytes\n";
} else {
    echo "❌ Парсинг не удался\n";
}
?>