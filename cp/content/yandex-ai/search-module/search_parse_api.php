<?php
// /fordzap.com/cp/content/yandex-ai/search-module/search_parse_api.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/YandexSearch.php';
require_once __DIR__ . '/../classes/BlacklistManager.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function writeLogApi($msg, $type = 'info') {
    $logFile = __DIR__ . '/../logs/search_parse.log';
    $dir = dirname($logFile);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " [$type] $msg\n", FILE_APPEND);
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_GET;
    $query = trim($input['q'] ?? $input['query'] ?? '');
    if (empty($query)) throw new Exception('Параметр "q" обязателен');

    $searchOptions = [
        'max_results' => min(max((int)($input['max_results'] ?? 10), 1), 50),
        'page' => (int)($input['page'] ?? 0),
        'region' => $input['region'] ?? '225',
        'use_cache' => (bool)($input['search_use_cache'] ?? true),
        'sort' => $input['sort'] ?? 'relevance',
        'max_pages' => (int)($input['max_pages'] ?? 2)
    ];

    $parseOptions = [
        'parallel' => min(max((int)($input['parse_parallel'] ?? 3), 1), 10),
        'wait_for' => (int)($input['parse_wait_for'] ?? 3000),
        'no_cache' => (bool)($input['parse_no_cache'] ?? false)
    ];

    writeLogApi("Query: $query");

    // 1. Поиск
    $search = new YandexSearch();
    $searchResult = $search->search($query, $searchOptions);
    if (!$searchResult['success'] || empty($searchResult['results'])) {
        echo json_encode(['success' => false, 'error' => 'Поиск не дал результатов', 'search' => $searchResult]);
        exit;
    }

    $urls = array_column($searchResult['results'], 'url');
    writeLogApi("Found " . count($urls) . " URLs");

    // 2. Парсинг
    $protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
    $parserUrl = 'https://fordzap.com/cp/content/yandex-ai/parser-module/parser_api.php';
    $payload = ['action' => 'parse_multiple', 'urls' => $urls, 'parallel' => $parseOptions['parallel'], 'wait_for' => $parseOptions['wait_for'], 'no_cache' => $parseOptions['no_cache']];

    $ch = curl_init($parserUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $parserResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$parserResponse) throw new Exception("Ошибка парсера: HTTP $httpCode");
    $parseData = json_decode($parserResponse, true);
    if (!$parseData) throw new Exception("Невалидный JSON от парсера");

    // 3. Синхронизация чёрного списка (BlacklistManager)
    $blacklist = new BlacklistManager();
    if (!empty($parseData['urls_status'])) {
        foreach ($parseData['urls_status'] as $status) {
            if (($status['parse_status'] ?? '') === 'captcha') {
                $domain = parse_url($status['url'], PHP_URL_HOST);
                $blacklist->add($domain, 'captcha', ['url' => $status['url']]);
                writeLogApi("Blacklisted: $domain");
            }
        }
    }

    // 4. Ответ
    echo json_encode([
        'success' => true,
        'query' => $query,
        'search' => [
            'total_found' => $searchResult['total_found'] ?? 0,
            'total_available' => $searchResult['total_available'] ?? 0,
            'results_count' => count($searchResult['results']),
            'filtered_skipped_blacklisted' => $searchResult['filtered']['skipped_blacklisted'] ?? 0,
            'items' => $searchResult['results']
        ],
        'parse' => [
            'success' => $parseData['success'] ?? false,
            'overall_structured' => $parseData['overall_structured'] ?? [],
            'selling_description' => $parseData['selling_description'] ?? '',
            'urls_status' => $parseData['urls_status'] ?? [],
            'stats' => $parseData['stats'] ?? []
        ],
        'request' => ['search_options' => $searchOptions, 'parse_options' => $parseOptions, 'timestamp' => time()]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    writeLogApi("Error: " . $e->getMessage(), 'error');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>