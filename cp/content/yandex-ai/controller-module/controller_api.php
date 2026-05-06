<?php
// /fordzap.com/cp/content/yandex-ai/controller-module/controller_api.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/classes/SearchController.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $input = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
    } else {
        $input = $_GET;
    }
    
    $action = $input['action'] ?? 'search_and_parse';
    $query = $input['q'] ?? $input['query'] ?? '';
    $urls = $input['urls'] ?? [];
    $format = $input['format'] ?? 'json';
    
    writeLog("Controller API request: action=$action, query=$query, format=$format", 'controller');
    
    $controller = new SearchController();
    
    switch ($action) {
        case 'search_and_parse':
            if (empty($query)) {
                throw new Exception('Query parameter "q" is required');
            }
            
            $options = [
                'max_results' => $input['max_results'] ?? 10,
                'max_parse' => $input['max_parse'] ?? 50,
                'skip_blacklisted' => $input['skip_blacklisted'] ?? true,
                'search_options' => [
                    'max_results' => $input['search_max'] ?? 50,
                    'region' => $input['region'] ?? SEARCH_REGION,
                    'use_cache' => $input['use_cache'] ?? true
                ],
                'parse_options' => [
                    'use_cache' => $input['parse_cache'] ?? true,
                    'timeout' => $input['timeout'] ?? PARSER_TIMEOUT
                ]
            ];
            
            $result = $controller->searchAndParse($query, $options);
            
            // Если запрошен определенный формат
            if ($format !== 'json') {
                $formatted = $controller->formatForGenerator($result, $format);
                
                if ($format === 'plain' || $format === 'minimal') {
                    header('Content-Type: text/plain; charset=utf-8');
                    echo $formatted;
                } else {
                    echo json_encode($formatted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }
            } else {
                echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'parse_urls':
            if (empty($urls)) {
                throw new Exception('URLs array is required');
            }
            
            if (!is_array($urls)) {
                $urls = [$urls];
            }
            
            $options = [
                'skip_blacklisted' => $input['skip_blacklisted'] ?? true,
                'parse_options' => [
                    'use_cache' => $input['use_cache'] ?? true
                ]
            ];
            
            $result = $controller->parseUrls($urls, $options);
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;
            
        case 'format':
			// Форматирование ранее полученных результатов
			$data = $input['data'] ?? null;
			$format = $input['format'] ?? 'plain';
			
			if (!$data) {
				throw new Exception('Data to format is required');
			}
			
			writeLog("Format action called with format: $format", 'controller');
			
			// Простейшая проверка
			if ($format === 'plain') {
				$output = "Запрос: " . ($data['query'] ?? 'Неизвестно') . "\n\n";
				$output .= "Результаты:\n";
				
				if (isset($data['results']) && is_array($data['results'])) {
					foreach ($data['results'] as $i => $item) {
						$output .= ($i+1) . ". " . ($item['title'] ?? 'Без названия') . "\n";
						$output .= "   " . ($item['url'] ?? 'Нет URL') . "\n";
						if (isset($item['parsed']['text'])) {
							$output .= "   Текст: " . substr($item['parsed']['text'], 0, 5000) . "...\n\n";
						}
					}
				}
				
				header('Content-Type: text/plain; charset=utf-8');
				echo $output;
				exit;
			}
			
			$formatted = $controller->formatForGenerator($data, $format);
			
			if ($format === 'plain' || $format === 'minimal') {
				header('Content-Type: text/plain; charset=utf-8');
				echo $formatted;
			} else {
				echo json_encode($formatted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			}
			break;
            
        case 'stats':
            echo json_encode([
                'success' => true,
                'data' => $controller->getStats()
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;
		case 'get_formatted_text':
			if (empty($query)) {
				throw new Exception('Query parameter "q" is required');
			}
			
			// Выполняем поиск и парсинг
			$options = [
				'max_results' => $input['max_results'] ?? 10,
				'max_parse' => $input['max_parse'] ?? 5,
				'skip_blacklisted' => $input['skip_blacklisted'] ?? true
			];
			
			$result = $controller->searchAndParse($query, $options);
			
			// Форматируем как plain text
			$formatted = $controller->formatForGenerator($result, 'plain');
			
			header('Content-Type: text/plain; charset=utf-8');
			header('Content-Disposition: attachment; filename="search_results.txt"');
			echo $formatted;
			break;
        case 'clear_cache':
            $controller->clearCache();
            echo json_encode(['success' => true, 'message' => 'All caches cleared']);
            break;
            
        default:
            throw new Exception("Unknown action: $action");
    }
    
} catch (Exception $e) {
    writeLog("Controller API error: " . $e->getMessage(), 'error');
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>