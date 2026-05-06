<?php
// /fordzap.com/cp/content/yandex-ai/search-module/search_api.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/YandexSearch.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Обработка OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Получаем параметры
    $input = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
    } else {
        $input = $_GET;
    }
    
    $query = trim($input['q'] ?? $input['query'] ?? '');
    
    if (empty($query)) {
        throw new Exception('Параметр "q" (поисковый запрос) обязателен');
    }
    
    // Опции поиска
    $options = [
        'max_results' => isset($input['max_results']) ? (int)$input['max_results'] : 10,
        'page' => isset($input['page']) ? (int)$input['page'] : 0,
        'region' => $input['region'] ?? '225',
        'use_cache' => isset($input['use_cache']) ? (bool)$input['use_cache'] : true,
        'sort' => $input['sort'] ?? 'relevance'
    ];
    
    // Ограничиваем max_results
    if ($options['max_results'] < 1 || $options['max_results'] > 100) {
        $options['max_results'] = min(max($options['max_results'], 1), 100);
    }
    
    writeLog("Search API request: $query", 'api');
    
    // Выполняем поиск
    $search = new YandexSearch();
    $result = $search->search($query, $options);
    
    // Добавляем метаданные запроса
    $result['request'] = [
        'query' => $query,
        'options' => $options,
        'timestamp' => time()
    ];
    
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    writeLog("Search API error: " . $e->getMessage(), 'error');
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>