<?php
// /fordzap.com/cp/content/yandex-ai/parser-module/parser_api.php

error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

define('API_BASE_URL', 'https://api.fordzap.com');
define('TIMEOUT', 120);
define('POLL_DELAY', 1);
define('CACHE_DIR', __DIR__ . '/cache');
define('BLACKLIST_FILE', __DIR__ . '/blacklist.json');
define('CACHE_TTL', 86400);

if (!is_dir(CACHE_DIR)) mkdir(CACHE_DIR, 0755, true);

function isDomainBlacklisted($domain) {
    $domain = normalizeDomain($domain);
    if (!file_exists(BLACKLIST_FILE)) return false;
    $data = json_decode(file_get_contents(BLACKLIST_FILE), true);
    if (!is_array($data)) return false;
    foreach ($data as $entry) {
        if ($entry['domain'] === $domain && time() - $entry['timestamp'] < 3600) {
            return true;
        }
    }
    return false;
}

function addToBlacklist($domain, $reason, $details = []) {
    $domain = normalizeDomain($domain);
    $data = [];
    if (file_exists(BLACKLIST_FILE)) {
        $data = json_decode(file_get_contents(BLACKLIST_FILE), true);
        if (!is_array($data)) $data = [];
    }
    $data = array_filter($data, function($entry) use ($domain) {
        return $entry['domain'] !== $domain;
    });
    $data[] = [
        'domain' => $domain,
        'reason' => $reason,
        'details' => $details,
        'timestamp' => time()
    ];
    if (count($data) > 100) $data = array_slice($data, -100);
    file_put_contents(BLACKLIST_FILE, json_encode($data, JSON_PRETTY_UNESCAPED_UNICODE));
}

function normalizeDomain($domain) {
    $domain = preg_replace('/^www\./', '', $domain);
    return strtolower(trim($domain));
}

function getCacheKey($url) {
    return md5($url);
}

function getFromCache($url) {
    $file = CACHE_DIR . '/' . getCacheKey($url) . '.json';
    if (!file_exists($file)) return null;
    $data = json_decode(file_get_contents($file), true);
    if (!$data) return null;
    if (time() - $data['cached_at'] > CACHE_TTL) {
        unlink($file);
        return null;
    }
    return $data['result'];
}

function saveToCache($url, $result) {
    $file = CACHE_DIR . '/' . getCacheKey($url) . '.json';
    $data = [
        'cached_at' => time(),
        'url' => $url,
        'result' => $result
    ];
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
}

function requestVps($method, $endpoint, $data = null) {
    $url = API_BASE_URL . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200) return null;
    return json_decode($response, true);
}

function waitForTask($taskId) {
    for ($i = 0; $i < TIMEOUT; $i++) {
        $result = requestVps('GET', "/api/task/$taskId");
        if ($result && isset($result['status'])) {
            if ($result['status'] === 'completed') {
                return $result['result'] ?? $result;
            }
            if ($result['status'] === 'failed') {
                return null;
            }
        }
        sleep(POLL_DELAY);
    }
    return null;
}

// ---------- Одиночный парсинг ----------
function parseSingleUrl($url, $waitFor = 1000, $noCache = false) {
    $domain = parse_url($url, PHP_URL_HOST);
    if (isDomainBlacklisted($domain)) {
        return [
            'success' => false,
            'error' => 'Domain is blacklisted',
            'parse_status' => 'blacklisted'
        ];
    }

    if (!$noCache) {
        $cached = getFromCache($url);
        if ($cached) {
            $cached['cached'] = true;
            return $cached;
        }
    }

    $taskData = requestVps('POST', '/api/parse', [
        'url' => $url,
        'wait_for' => $waitFor,
        'no_cache' => $noCache
    ]);
    if (!$taskData || !isset($taskData['task_id'])) {
        return ['success' => false, 'error' => 'Failed to create task'];
    }

    $result = waitForTask($taskData['task_id']);
    if (!$result) {
        return ['success' => false, 'error' => 'Task failed or timeout'];
    }

    $isCaptcha = false;
    if (isset($result['urls_status']) && is_array($result['urls_status'])) {
        foreach ($result['urls_status'] as $st) {
            if (($st['url'] ?? '') === $url && ($st['parse_status'] ?? '') === 'captcha') {
                $isCaptcha = true;
                break;
            }
        }
    }
    if ($isCaptcha || (isset($result['status']) && $result['status'] === 'captcha')) {
        addToBlacklist($domain, 'captcha', ['url' => $url]);
        return ['success' => false, 'error' => 'Captcha detected', 'parse_status' => 'captcha'];
    }

    if (isset($result['error'])) {
        addToBlacklist($domain, 'parse_error', ['error' => $result['error']]);
        return ['success' => false, 'error' => $result['error']];
    }

    $response = [
        'success' => true,
        'url' => $url,
        'overall_structured' => $result['overall_structured'] ?? [],
        'selling_description' => $result['selling_description'] ?? '',
        'urls_status' => $result['urls_status'] ?? [],
        'cached' => false,
        'raw_html' => $result['html'] ?? '',
        'title' => $result['title'] ?? '',
        'text' => $result['text'] ?? '',
        'status_code' => $result['status_code'] ?? 0,
        'method' => $result['method'] ?? ''
    ];

    saveToCache($url, $response);
    return $response;
}

// ---------- Множественный парсинг (batch) ----------
function parseBatchUrls($urls, $parallel = 2, $waitFor = 1000, $noCache = false) {
    $results = [];
    $urlsToParse = [];
    $stats = ['cached' => 0, 'blacklisted' => 0, 'failed' => 0, 'success' => 0];

    foreach ($urls as $url) {
        $domain = parse_url($url, PHP_URL_HOST);
        if (isDomainBlacklisted($domain)) {
            $results[$url] = ['success' => false, 'error' => 'Domain is blacklisted', 'parse_status' => 'blacklisted'];
            $stats['blacklisted']++;
            continue;
        }

        if (!$noCache) {
            $cached = getFromCache($url);
            if ($cached) {
                $cached['cached'] = true;
                $results[$url] = $cached;
                $stats['cached']++;
                continue;
            }
        }
        $urlsToParse[] = $url;
    }

    $globalStructured = [];
    $globalSelling = '';
    $globalUrlsStatus = [];

    if (!empty($urlsToParse)) {
        $taskData = requestVps('POST', '/api/parse-batch', [
            'urls' => $urlsToParse,
            'parallel' => $parallel,
            'wait_for' => $waitFor,
            'no_cache' => $noCache
        ]);
        if ($taskData && isset($taskData['task_id'])) {
            $batchResult = waitForTask($taskData['task_id']);
            if ($batchResult && is_array($batchResult)) {
                // Извлекаем глобальные данные (новая структура)
                if (isset($batchResult['overall_structured'])) {
                    $globalStructured = $batchResult['overall_structured'];
                    $globalSelling = $batchResult['selling_description'] ?? '';
                    $globalUrlsStatus = $batchResult['urls_status'] ?? [];
                }
                // Обрабатываем результаты
                $items = $batchResult['results'] ?? $batchResult;
                if (is_array($items)) {
                    foreach ($items as $item) {
                        $url = $item['url'] ?? '';
                        if (!$url) continue;
                        $domain = parse_url($url, PHP_URL_HOST);
                        $isCaptcha = false;
                        if (!empty($globalUrlsStatus)) {
                            foreach ($globalUrlsStatus as $st) {
                                if (($st['url'] ?? '') === $url && ($st['parse_status'] ?? '') === 'captcha') {
                                    $isCaptcha = true;
                                    break;
                                }
                            }
                        }
                        if ($isCaptcha) {
                            addToBlacklist($domain, 'captcha', ['url' => $url]);
                            $results[$url] = ['success' => false, 'error' => 'Captcha detected', 'parse_status' => 'captcha'];
                            $stats['failed']++;
                            continue;
                        }
                        if (isset($item['error'])) {
                            addToBlacklist($domain, 'parse_error', ['error' => $item['error']]);
                            $results[$url] = ['success' => false, 'error' => $item['error']];
                            $stats['failed']++;
                            continue;
                        }
                        $response = [
                            'success' => true,
                            'url' => $url,
                            'overall_structured' => $globalStructured ?: ($item['overall_structured'] ?? []),
                            'selling_description' => $globalSelling ?: ($item['selling_description'] ?? ''),
                            'urls_status' => $globalUrlsStatus ?: ($item['urls_status'] ?? []),
                            'cached' => false,
                            'raw_html' => $item['html'] ?? '',
                            'title' => $item['title'] ?? '',
                            'text' => $item['text'] ?? '',
                            'status_code' => $item['status_code'] ?? 0,
                            'method' => $item['method'] ?? ''
                        ];
                        $results[$url] = $response;
                        $stats['success']++;
                        saveToCache($url, $response);
                    }
                } else {
                    foreach ($urlsToParse as $url) {
                        $results[$url] = ['success' => false, 'error' => 'Invalid batch response'];
                        $stats['failed']++;
                    }
                }
            } else {
                foreach ($urlsToParse as $url) {
                    $results[$url] = ['success' => false, 'error' => 'Batch API request failed (no result)'];
                    $stats['failed']++;
                }
            }
        } else {
            foreach ($urlsToParse as $url) {
                $results[$url] = ['success' => false, 'error' => 'Failed to create batch task'];
                $stats['failed']++;
            }
        }
    }

    $ordered = [];
    foreach ($urls as $url) {
        $ordered[$url] = $results[$url] ?? ['success' => false, 'error' => 'Unknown error'];
    }

    $response = [
        'success' => true,
        'results' => $ordered,
        'stats' => $stats
    ];
    if (!empty($globalStructured)) {
        $response['overall_structured'] = $globalStructured;
        $response['selling_description'] = $globalSelling;
        $response['urls_status'] = $globalUrlsStatus;
    }
    return $response;
}

// ---------- Основной обработчик ----------
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_GET;

$action = $input['action'] ?? 'parse';

if ($action === 'parse') {
    $url = trim($input['url'] ?? '');
    if (!$url) {
        echo json_encode(['success' => false, 'error' => 'URL required']);
        exit;
    }
    $waitFor = (int)($input['wait_for'] ?? 1000);
    $noCache = !empty($input['no_cache']);
    $result = parseSingleUrl($url, $waitFor, $noCache);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
elseif ($action === 'parse_multiple') {
    $urls = $input['urls'] ?? [];
    if (!is_array($urls)) $urls = [];
    if (empty($urls)) {
        echo json_encode(['success' => false, 'error' => 'URLs array required']);
        exit;
    }
    $parallel = (int)($input['parallel'] ?? 2);
    $waitFor = (int)($input['wait_for'] ?? 1000);
    $noCache = !empty($input['no_cache']);
    $result = parseBatchUrls($urls, $parallel, $waitFor, $noCache);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
else {
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
?>