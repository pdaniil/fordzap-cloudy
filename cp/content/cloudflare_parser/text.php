<?php
// test.php - полное тестирование Cloudflare Browser Parser
require_once 'config.php';
require_once 'CloudflareBrowser.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Cloudflare Browser Parser - Full Test</title>
    <meta charset='utf-8'>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; }
        .result { background: white; padding: 20px; border-radius: 8px; margin-top: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; max-height: 300px; border-radius: 4px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 10px 0; }
        .stat-item { background: #f0f0f0; padding: 10px; border-radius: 4px; }
        .stat-label { font-weight: bold; color: #555; }
        .stat-value { font-size: 1.2em; color: #2196F3; }
        button { padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #1976D2; }
        .image-preview { max-width: 100%; max-height: 400px; border: 1px solid #ddd; }
        .tab { overflow: hidden; border: 1px solid #ccc; background-color: #f1f1f1; border-radius: 4px 4px 0 0; }
        .tab button { background-color: inherit; float: left; border: none; outline: none; cursor: pointer; padding: 14px 16px; transition: 0.3s; color: black; margin: 0; }
        .tab button:hover { background-color: #ddd; }
        .tab button.active { background-color: #2196F3; color: white; }
        .tabcontent { display: none; padding: 20px; border: 1px solid #ccc; border-top: none; border-radius: 0 0 4px 4px; background: white; }
        .tabcontent.active { display: block; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🌐 Cloudflare Browser Parser - Full Test</h1>";

try {
    $browser = new CloudflareBrowser(null, null, true);
    $testUrl = "https://sv62.ru/products/OSSCA/19210.html";
    
    // Тест соединения
    echo "<div class='result'>";
    echo "<h2>📡 Connection Test</h2>";
    
    if ($browser->testConnection()) {
        echo "<p class='success'>✓ Connection successful!</p>";
        echo "<div class='stats'>";
        echo "<div class='stat-item'><span class='stat-label'>Account ID:</span> <span class='stat-value'>" . substr(CLOUDFLARE_ACCOUNT_ID, 0, 8) . "...</span></div>";
        echo "<div class='stat-item'><span class='stat-label'>API Token:</span> <span class='stat-value'>" . substr(CLOUDFLARE_API_TOKEN, 0, 8) . "...</span></div>";
        echo "</div>";
    } else {
        echo "<p class='error'>✗ Connection failed: " . $browser->getLastError() . "</p>";
    }
    echo "</div>";
    
    // Табы для разных функций
    echo "<div class='result'>";
    echo "<h2>🧪 Function Tests</h2>";
    
    echo "<div class='tab'>";
    echo "<button class='tablinks active' onclick='openTab(event, \"HTML\")'>HTML</button>";
    echo "<button class='tablinks' onclick='openTab(event, \"Markdown\")'>Markdown</button>";
    echo "<button class='tablinks' onclick='openTab(event, \"Metadata\")'>Metadata</button>";
    echo "<button class='tablinks' onclick='openTab(event, \"Screenshot\")'>Screenshot</button>";
    echo "</div>";
    
    // HTML Tab
    echo "<div id='HTML' class='tabcontent active'>";
    echo "<h3>HTML Content from $testUrl</h3>";
    $startTime = microtime(true);
    $html = $browser->fetchHtml($testUrl);
    $htmlTime = round((microtime(true) - $startTime) * 1000);
    
    if ($html) {
        echo "<div class='stats'>";
        echo "<div class='stat-item'><span class='stat-label'>Length:</span> <span class='stat-value'>" . strlen($html) . " chars</span></div>";
        echo "<div class='stat-item'><span class='stat-label'>Size:</span> <span class='stat-value'>" . round(strlen($html) / 1024, 2) . " KB</span></div>";
        echo "<div class='stat-item'><span class='stat-label'>Time:</span> <span class='stat-value'>" . $htmlTime . " ms</span></div>";
        echo "</div>";
        echo "<pre>" . htmlspecialchars(substr($html, 0, 1000)) . "...</pre>";
        echo "<p><a href='#' onclick='document.getElementById(\"full-html\").style.display=\"block\"; return false;'>Show full HTML</a></p>";
        echo "<div id='full-html' style='display:none'><pre>" . htmlspecialchars($html) . "</pre></div>";
    } else {
        echo "<p class='error'>Failed to fetch HTML</p>";
    }
    echo "</div>";
    
    // Markdown Tab
    echo "<div id='Markdown' class='tabcontent'>";
    echo "<h3>Markdown from $testUrl</h3>";
    $startTime = microtime(true);
    $markdown = $browser->fetchMarkdown($testUrl);
    $mdTime = round((microtime(true) - $startTime) * 1000);
    
    if ($markdown) {
        echo "<div class='stats'>";
        echo "<div class='stat-item'><span class='stat-label'>Length:</span> <span class='stat-value'>" . strlen($markdown) . " chars</span></div>";
        echo "<div class='stat-item'><span class='stat-label'>Size:</span> <span class='stat-value'>" . round(strlen($markdown) / 1024, 2) . " KB</span></div>";
        echo "<div class='stat-item'><span class='stat-label'>Time:</span> <span class='stat-value'>" . $mdTime . " ms</span></div>";
        echo "</div>";
        echo "<pre>" . htmlspecialchars(substr($markdown, 0, 1000)) . "...</pre>";
    } else {
        echo "<p class='error'>Failed to fetch Markdown</p>";
    }
    echo "</div>";
    
    // Metadata Tab
    echo "<div id='Metadata' class='tabcontent'>";
    echo "<h3>Metadata from $testUrl</h3>";
    $startTime = microtime(true);
    $metadata = $browser->fetchMetadata($testUrl);
    $metaTime = round((microtime(true) - $startTime) * 1000);
    
    if ($metadata) {
        echo "<div class='stats'>";
        echo "<div class='stat-item'><span class='stat-label'>Time:</span> <span class='stat-value'>" . $metaTime . " ms</span></div>";
        echo "</div>";
        echo "<pre>" . htmlspecialchars(print_r($metadata, true)) . "</pre>";
    } else {
        echo "<p class='error'>Failed to fetch metadata</p>";
    }
    echo "</div>";
    
    // Screenshot Tab
    echo "<div id='Screenshot' class='tabcontent'>";
    echo "<h3>Screenshot of $testUrl</h3>";
    
    // Тест с разными опциями
    $screenshotOptions = [
        'simple' => [],
        'full_page' => ['screenshot' => ['full_page' => true, 'type' => 'png']]
    ];
    
    foreach ($screenshotOptions as $name => $options) {
        echo "<h4>" . ucfirst(str_replace('_', ' ', $name)) . " Screenshot:</h4>";
        $startTime = microtime(true);
        $screenshot = $browser->fetchScreenshot($testUrl, $options);
        $shotTime = round((microtime(true) - $startTime) * 1000);
        
        if ($screenshot) {
            echo "<div class='stats'>";
            echo "<div class='stat-item'><span class='stat-label'>Size:</span> <span class='stat-value'>" . round(strlen($screenshot) / 1024, 2) . " KB (base64)</span></div>";
            echo "<div class='stat-item'><span class='stat-label'>Time:</span> <span class='stat-value'>" . $shotTime . " ms</span></div>";
            echo "</div>";
            echo "<img src='data:image/png;base64,$screenshot' class='image-preview'>";
            
            // Тест сохранения в файл
            $filename = "screenshot_" . time() . "_$name.png";
            $filepath = __DIR__ . "/$filename";
            if ($browser->saveScreenshot($testUrl, $filepath, $options)) {
                echo "<p class='success'>✓ Saved to: $filename (" . round(filesize($filepath) / 1024, 2) . " KB)</p>";
            }
        } else {
            echo "<p class='error'>Failed to take screenshot</p>";
        }
        echo "<hr>";
    }
    echo "</div>";
    
    echo "</div>"; // закрываем result
    
    // Тест с реальным сайтом (exist.ru)
    echo "<div class='result'>";
    echo "<h2>🌍 Testing with exist.ru</h2>";
    
    $realUrl = "https://exist.ru/Catalog/Goods/18/14/DF40D2D6";
    echo "<p>Testing URL: <a href='$realUrl' target='_blank'>$realUrl</a></p>";
    
    // HTML
    echo "<h3>HTML Fetch:</h3>";
    $startTime = microtime(true);
    $realHtml = $browser->fetchHtml($realUrl);
    $realHtmlTime = round((microtime(true) - $startTime) * 1000);
    
    if ($realHtml) {
        echo "<p class='success'>✓ HTML fetched successfully</p>";
        echo "<div class='stats'>";
        echo "<div class='stat-item'><span class='stat-label'>Size:</span> <span class='stat-value'>" . round(strlen($realHtml) / 1024, 2) . " KB</span></div>";
        echo "<div class='stat-item'><span class='stat-label'>Time:</span> <span class='stat-value'>" . $realHtmlTime . " ms</span></div>";
        echo "</div>";
        
        // Ищем цену в HTML (пример парсинга)
        if (preg_match('/<span class="price">([^<]+)<\/span>/', $realHtml, $matches)) {
            echo "<p>Found price: " . $matches[1] . "</p>";
        }
    } else {
        echo "<p class='error'>Failed to fetch HTML from exist.ru</p>";
    }
    
    // Screenshot
    echo "<h3>Screenshot:</h3>";
    $realScreenshot = $browser->fetchScreenshot($realUrl, [
        'viewport' => ['width' => 1280, 'height' => 1024],
        'screenshot' => ['type' => 'jpeg', 'quality' => 80]
    ]);
    
    if ($realScreenshot) {
        echo "<img src='data:image/jpeg;base64,$realScreenshot' class='image-preview' style='max-width:100%'>";
        
        // Сохраняем скриншот
        $filename = "exist_" . date('Y-m-d_H-i-s') . ".jpg";
        $imageData = base64_decode($realScreenshot);
        file_put_contents(__DIR__ . "/$filename", $imageData);
        echo "<p class='success'>✓ Screenshot saved as: $filename</p>";
    }
    
    echo "</div>";
    
    // Performance тест
    echo "<div class='result'>";
    echo "<h2>⚡ Performance Test</h2>";
    
    $urls = [
        'Example.com' => 'https://example.com',
        'Google' => 'https://www.google.com',
        'GitHub' => 'https://github.com'
    ];
    
    echo "<table style='width:100%; border-collapse: collapse;'>";
    echo "<tr style='background:#f0f0f0;'><th>Site</th><th>HTML Time</th><th>Markdown Time</th><th>Screenshot Time</th></tr>";
    
    foreach ($urls as $name => $url) {
        echo "<tr>";
        echo "<td><strong>$name</strong></td>";
        
        // HTML
        $start = microtime(true);
        $browser->fetchHtml($url);
        $htmlTime = round((microtime(true) - $start) * 1000);
        echo "<td>" . ($htmlTime ? $htmlTime . " ms" : "Failed") . "</td>";
        
        // Markdown
        $start = microtime(true);
        $browser->fetchMarkdown($url);
        $mdTime = round((microtime(true) - $start) * 1000);
        echo "<td>" . ($mdTime ? $mdTime . " ms" : "Failed") . "</td>";
        
        // Screenshot
        $start = microtime(true);
        $browser->fetchScreenshot($url, ['screenshot' => ['type' => 'jpeg', 'quality' => 50]]);
        $shotTime = round((microtime(true) - $start) * 1000);
        echo "<td>" . ($shotTime ? $shotTime . " ms" : "Failed") . "</td>";
        
        echo "</tr>";
    }
    
    echo "</table>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='result'>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "</div>";

// JavaScript для табов
echo "
<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName('tabcontent');
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = 'none';
    }
    tablinks = document.getElementsByClassName('tablinks');
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(' active', '');
    }
    document.getElementById(tabName).style.display = 'block';
    evt.currentTarget.className += ' active';
}
</script>

</body>
</html>";
?>