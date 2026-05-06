<?php
// /fordzap.com/cp/content/yandex-ai/controller-module/classes/SearchController.php

require_once __DIR__ . '/../../classes/YandexSearch.php';
require_once __DIR__ . '/../../classes/PageParser.php';
require_once __DIR__ . '/../../classes/BlacklistManager.php';

class SearchController {
    private $search;
    private $parser;
    private $blacklist;
    private $stats = [
        'total_requests' => 0,
        'search_requests' => 0,
        'parse_requests' => 0,
        'successful_parses' => 0,
        'failed_parses' => 0,
        'blacklisted_skipped' => 0
    ];
    
    public function __construct() {
        $this->search = new YandexSearch();
        $this->parser = new PageParser();
        $this->blacklist = new BlacklistManager();
        writeLog("SearchController initialized", 'controller');
    }
    
    /**
     * Основной метод - поиск + парсинг всех результатов
     */
    public function searchAndParse($query, $options = []) {
        $this->stats['total_requests']++;
        $this->stats['search_requests']++;
        
        writeLog("Controller: searchAndParse for '$query'", 'controller');
        
        $defaults = [
            'max_results' => 10,
            'parse_all' => true,
            'max_parse' => 5,
            'skip_blacklisted' => true,
            'timeout' => 30,
            'search_options' => [],
            'parse_options' => []
        ];
        
        $options = array_merge($defaults, $options);
        
        // Шаг 1: Поиск
        $searchResults = $this->search->search($query, $options['search_options']);
        
        if (!$searchResults['success']) {
            return [
                'success' => false,
                'error' => 'Search failed: ' . ($searchResults['error'] ?? 'Unknown error'),
                'stats' => $this->stats
            ];
        }
        
        // Шаг 2: Подготовка URL для парсинга
        $urlsToParse = [];
        $skippedBlacklisted = 0;
        
        foreach ($searchResults['results'] as $result) {
            if (count($urlsToParse) >= $options['max_parse']) {
                break;
            }
            
            $domain = parse_url($result['url'], PHP_URL_HOST);
            $domain = preg_replace('/^www\./', '', $domain);
            
            if ($options['skip_blacklisted'] && $this->blacklist->isBlacklisted($domain)) {
                $skippedBlacklisted++;
                $this->stats['blacklisted_skipped']++;
                writeLog("Skipping blacklisted domain: $domain", 'controller');
                continue;
            }
            
            $urlsToParse[] = [
                'url' => $result['url'],
                'title' => $result['title'],
                'domain' => $domain,
                'position' => $result['position'] ?? null,
                'snippet' => $result['snippet'] ?? ''
            ];
        }
        
        // Шаг 3: Парсинг
        $parsedResults = [];
        $parseStats = [
            'success' => 0,
            'failed' => 0,
            'total' => count($urlsToParse)
        ];
        
        foreach ($urlsToParse as $item) {
            $this->stats['parse_requests']++;
            
            writeLog("Parsing URL: {$item['url']}", 'controller');
            
            $parseResult = $this->parser->parse($item['url'], $options['parse_options']);
            
            $resultItem = [
                'url' => $item['url'],
                'title' => $item['title'],
                'domain' => $item['domain'],
                'position' => $item['position'],
                'snippet' => $item['snippet']
            ];
            
            if ($parseResult['success']) {
                $parseStats['success']++;
                $this->stats['successful_parses']++;
                $resultItem['parsed'] = $parseResult;
                $resultItem['success'] = true;
            } else {
                $parseStats['failed']++;
                $this->stats['failed_parses']++;
                $resultItem['error'] = $parseResult['error'] ?? 'Parse failed';
                $resultItem['http_code'] = $parseResult['http_code'] ?? null;
                $resultItem['success'] = false;
            }
            
            $parsedResults[] = $resultItem;
            
            usleep(300000);
        }
        
        // Формируем результат
        $result = [
            'success' => true,
            'query' => $query,
            'timestamp' => time(),
            'search' => [
                'total_found' => $searchResults['total_found'] ?? 0,
                'results_count' => $searchResults['results_count'] ?? 0,
                'filtered' => $searchResults['filtered'] ?? null
            ],
            'parse' => [
                'requested' => count($urlsToParse),
                'success' => $parseStats['success'],
                'failed' => $parseStats['failed'],
                'skipped_blacklisted' => $skippedBlacklisted
            ],
            'results' => $parsedResults,
            'stats' => $this->stats
        ];
        
        writeLog("Controller completed: parsed {$parseStats['success']}/{$parseStats['total']} URLs", 'controller');
        
        return $result;
    }
    
    /**
	 * Форматирование результатов для модуля генерации
	 */
	public function formatForGenerator($data, $format = 'plain') {
		writeLog("Formatting data with format: $format", 'controller');
		
		if (!$data['success']) {
			writeLog("Format error: data not successful", 'error');
			return "Ошибка: " . ($data['error'] ?? 'Неизвестная ошибка');
		}
		
		// Проверяем структуру данных
		if (!isset($data['results'])) {
			writeLog("Format error: missing results in data", 'error');
			return "Ошибка: отсутствуют результаты";
		}
		
		$formats = [
			'plain' => function($data) {
				writeLog("Using plain formatter", 'controller');
				return $this->formatPlain($data);
			},
			'json' => function($data) {
				writeLog("Using JSON formatter", 'controller');
				return json_encode($this->formatStructured($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			},
			'minimal' => function($data) {
				writeLog("Using minimal formatter", 'controller');
				return $this->formatMinimal($data);
			},
			'structured' => function($data) {
				writeLog("Using structured formatter", 'controller');
				return $this->formatStructured($data);
			}
		];
		
		if (!isset($formats[$format])) {
			writeLog("Unknown format: $format, using plain", 'warning');
			$format = 'plain';
		}
		
		try {
			$result = $formats[$format]($data);
			writeLog("Formatting successful, result length: " . strlen($result), 'controller');
			return $result;
		} catch (Exception $e) {
			writeLog("Formatting exception: " . $e->getMessage(), 'error');
			return "Ошибка форматирования: " . $e->getMessage();
		}
	}

	/**
	 * Plain text формат - с дополнительной проверкой
	 */
	private function formatPlain($data) {
		writeLog("Starting formatPlain", 'controller');
		
		$output = [];
		$output[] = "=== ПОИСКОВЫЙ ЗАПРОС: {$data['query']} ===\n";
		$output[] = "Найдено результатов: " . ($data['search']['results_count'] ?? 0);
		$output[] = "Успешно спарсено: " . ($data['parse']['success'] ?? 0) . " из " . ($data['parse']['requested'] ?? 0) . "\n";
		
		$resultCount = 0;
		foreach ($data['results'] as $index => $item) {
			$resultCount++;
			$output[] = "--- ИСТОЧНИК " . ($index + 1) . " ---";
			$output[] = "URL: " . ($item['url'] ?? 'Нет URL');
			$output[] = "Заголовок: " . ($item['title'] ?? 'Без заголовка');
			$output[] = "Домен: " . ($item['domain'] ?? 'Неизвестно');
			
			if (isset($item['success']) && $item['success'] && isset($item['parsed'])) {
				if (!empty($item['parsed']['title'])) {
					$output[] = "Заголовок страницы: " . $item['parsed']['title'];
				}
				if (!empty($item['parsed']['description'])) {
					$output[] = "Описание: " . $item['parsed']['description'];
				}
				
				// ПОЛНЫЙ ТЕКСТ БЕЗ ОБРЕЗКИ
				$content = $item['parsed']['text'] ?? '';
				if (!empty($content)) {
					$output[] = "\nТЕКСТ (" . strlen($content) . " символов):\n" . $content;
				} else {
					$output[] = "\nТЕКСТ: (пусто)";
				}
			} else {
				$error = $item['error'] ?? 'Не удалось загрузить';
				$output[] = "ОШИБКА: " . $error;
			}
			$output[] = str_repeat("-", 50) . "\n";
		}
		
		$output[] = "=== СТАТИСТИКА ===";
		$output[] = "Всего запросов контроллера: " . ($data['stats']['total_requests'] ?? 0);
		$output[] = "Успешных парсингов: " . ($data['stats']['successful_parses'] ?? 0);
		$output[] = "Ошибок парсинга: " . ($data['stats']['failed_parses'] ?? 0);
		$output[] = "Пропущено черным списком: " . ($data['stats']['blacklisted_skipped'] ?? 0);
		$output[] = "Обработано результатов: " . $resultCount;
		
		$final = implode("\n", $output);
		writeLog("formatPlain completed, output length: " . strlen($final), 'controller');
		
		return $final;
	}
    
    /**
     * Минимальный формат - только текст
     */
    private function formatMinimal($data) {
        $output = [];
        
        foreach ($data['results'] as $item) {
            if ($item['success'] && isset($item['parsed']['text'])) {
                $output[] = $item['parsed']['text'];
            }
        }
        
        return implode("\n\n---\n\n", $output);
    }
    
    /**
     * Структурированный формат для API
     */
    private function formatStructured($data) {
        $structured = [
            'query' => $data['query'],
            'timestamp' => $data['timestamp'],
            'sources' => []
        ];
        
        foreach ($data['results'] as $item) {
            if (!$item['success'] || !isset($item['parsed'])) {
                continue;
            }
            
            $structured['sources'][] = [
                'url' => $item['url'],
                'domain' => $item['domain'],
                'title' => $item['parsed']['title'] ?? $item['title'],
                'description' => $item['parsed']['description'] ?? '',
                'content' => $item['parsed']['text'] ?? '',
                'content_length' => strlen($item['parsed']['text'] ?? ''),
                'position' => $item['position']
            ];
        }
        
        $structured['metadata'] = [
            'total_sources' => count($structured['sources']),
            'search_results' => $data['search']['results_count'],
            'parse_stats' => $data['parse']
        ];
        
        return $structured;
    }
    
    /**
     * Только парсинг URL (без поиска)
     */
    public function parseUrls($urls, $options = []) {
        $this->stats['total_requests']++;
        
        writeLog("Controller: parseUrls, count=" . count($urls), 'controller');
        
        $defaults = [
            'skip_blacklisted' => true,
            'parse_options' => []
        ];
        
        $options = array_merge($defaults, $options);
        
        $results = [];
        $skipped = 0;
        
        foreach ($urls as $url) {
            $this->stats['parse_requests']++;
            
            $domain = parse_url($url, PHP_URL_HOST);
            $domain = preg_replace('/^www\./', '', $domain);
            
            if ($options['skip_blacklisted'] && $this->blacklist->isBlacklisted($domain)) {
                $skipped++;
                $this->stats['blacklisted_skipped']++;
                writeLog("Skipping blacklisted domain: $domain", 'controller');
                continue;
            }
            
            $parseResult = $this->parser->parse($url, $options['parse_options']);
            
            if ($parseResult['success']) {
                $this->stats['successful_parses']++;
            } else {
                $this->stats['failed_parses']++;
            }
            
            $results[$url] = $parseResult;
            
            usleep(300000);
        }
        
        return [
            'success' => true,
            'results' => $results,
            'stats' => [
                'total' => count($urls),
                'parsed' => count($results),
                'skipped_blacklisted' => $skipped,
                'controller_stats' => $this->stats
            ]
        ];
    }
    
    /**
     * Получить статистику контроллера
     */
    public function getStats() {
        return [
            'controller_stats' => $this->stats,
            'search_stats' => method_exists($this->search, 'getStats') ? $this->search->getStats() : null,
            'parser_stats' => $this->parser->getStats(),
            'blacklist' => $this->blacklist->getAll()
        ];
    }
    
    /**
     * Очистить кэш
     */
    public function clearCache() {
        if (method_exists($this->search, 'clearCache')) {
            $this->search->clearCache();
        }
        $this->parser->clearCache();
        writeLog("Controller: all caches cleared", 'controller');
    }
}
?>