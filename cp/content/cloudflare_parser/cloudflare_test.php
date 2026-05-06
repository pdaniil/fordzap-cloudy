<?php
// /fordzap.com/cp/content/cloudflare-parser/cloudflare_test.php

require_once 'config.php';
require_once 'CloudflareBrowser.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Обработка OPTIONS запроса для CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Получаем входные данные
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Поддержка GET параметров для простого тестирования
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $input = $_GET;
    }
    
    $url = $input['url'] ?? '';
    $type = $input['type'] ?? 'html';
    $debug = isset($input['debug']) ? filter_var($input['debug'], FILTER_VALIDATE_BOOLEAN) : false;
    
    if (empty($url)) {
        throw new Exception('URL is required');
    }
    
    // Валидация URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception('Invalid URL format');
    }
    
    $browser = new CloudflareBrowser(null, null, $debug);
    
    // Проверяем соединение с Cloudflare
    if (!$browser->testConnection()) {
        throw new Exception('Failed to connect to Cloudflare API. Check your credentials.');
    }
    
    $result = array('success' => true, 'type' => $type, 'url' => $url);
    
    switch ($type) {
        case 'html':
            $options = isset($input['options']) ? $input['options'] : array();
            $content = $browser->fetchHtml($url, $options);
            $result['content'] = $content;
            $result['length'] = strlen($content);
            $result['size_kb'] = round(strlen($content) / 1024, 2);
            break;
            
        case 'markdown':
            $content = $browser->fetchMarkdown($url);
            $result['content'] = $content;
            $result['length'] = strlen($content);
            $result['size_kb'] = round(strlen($content) / 1024, 2);
            break;
            
        case 'screenshot':
            $options = isset($input['options']) ? $input['options'] : array();
            $image = $browser->fetchScreenshot($url, $options);
            $result['image'] = $image;
            $result['format'] = isset($options['screenshot']['type']) ? $options['screenshot']['type'] : 'png';
            break;
            
        case 'pdf':
            $options = isset($input['options']) ? $input['options'] : array();
            $pdf = $browser->fetchPdf($url, $options);
            $result['pdf'] = $pdf;
            break;
            
        case 'metadata':
            $metadata = $browser->fetchMetadata($url);
            $result['metadata'] = $metadata;
            break;
            
        case 'full':
            // Получаем все данные сразу
            $result['html'] = $browser->fetchHtml($url);
            $result['metadata'] = $browser->fetchMetadata($url);
            $result['screenshot'] = $browser->fetchScreenshot($url, array(
                'screenshot' => array('type' => 'jpeg', 'quality' => 70)
            ));
            break;
            
        default:
            throw new Exception("Unknown type: $type");
    }
    
    // Добавляем информацию о времени выполнения
    $result['timestamp'] = time();
    $result['debug'] = $debug;
    
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => time()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>