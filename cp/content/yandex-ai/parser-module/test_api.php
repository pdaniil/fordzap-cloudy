<?php
// /fordzap.com/cp/content/yandex-ai/test_external_parser.php
ini_set("display_errors", 1);
error_reporting(E_ALL);
require_once $_SERVER["DOCUMENT_ROOT"] . '/cp/content/yandex-ai/config.php';
require_once $_SERVER["DOCUMENT_ROOT"] . '/cp/content/yandex-ai/classes/PageParser.php';

header('Content-Type: text/plain; charset=utf-8');

$parser = new PageParser();

$urls = array(
    'https://example.com',
    'https://ya.ru'
);

foreach ($urls as $url) {
    echo "\n=== $url ===\n";
    $result = $parser->parse($url);
    
    if ($result['success']) {
        echo "✅ Success\n";
        echo "Title: " . $result['title'] . "\n";
        echo "Description: " . $result['description'] . "\n";
        echo "Text length: " . strlen($result['text']) . "\n";
        echo "Cached: " . ($result['cached'] ? 'yes' : 'no') . "\n";
    } else {
        echo "❌ Error: " . $result['error'] . "\n";
    }
}

echo "\n=== STATS ===\n";
print_r($parser->getStats());
?>