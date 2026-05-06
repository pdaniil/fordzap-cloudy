<?php
// test_exist.php
require_once 'config.php';
require_once 'CloudflareBrowser.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Exist.ru</title>
    <meta charset='utf-8'>
    <style>
        body { font-family: Arial; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; max-height: 500px; }
    </style>
</head>
<body>
    <h1>Testing exist.ru with Cloudflare Browser</h1>";

$url = "https://exist.ru/Catalog/Goods/18/14/DF40D2D6";
echo "<p>URL: " . htmlspecialchars($url) . "</p>";

$browser = new CloudflareBrowser();

// Тест с правильными параметрами
echo "<h2>2. Loading page with gotoOptions</h2>";

$start = microtime(true);

$options = [
    'gotoOptions' => [
        'timeout' => 60000,
        'waitUntil' => 'networkidle2'
    ],
    'rejectResourceTypes' => ['image'],
	'rejectRequestPattern' => ["/^.*\\.(css)"],	
    'viewport' => ['width' => 1024, 'height' => 768]
];
$html = $browser->fetchHtml($url, $options);
$time = round((microtime(true) - $start), 2);

if ($html) {
    echo "<p class='success'>✓ Loaded in {$time} seconds</p>";
    echo "<p>Size: " . round(strlen($html) / 1024, 2) . " KB</p>";
    
    // Парсим название товара
    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/', $html, $matches)) {
        echo "<p>Product: " . htmlspecialchars($matches[1]) . "</p>";
    }
    
    // Парсим цену (если есть)
    if (preg_match('/<span class="price">([^<]+)<\/span>/', $html, $matches)) {
        echo "<p>Price: " . htmlspecialchars($matches[1]) . "</p>";
    }
    
    // Показываем первые 1000 символов
    echo "<h3>First 1000 characters:</h3>";
    echo "<pre>" . htmlspecialchars(substr($html, 0, 1000)) . "...</pre>";
    
} else {
    echo "<p class='error'>✗ Failed: " . $browser->getLastError() . "</p>";
}

echo "</body></html>";
?>