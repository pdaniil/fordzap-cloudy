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
            'parse_all' => true,              // Парсить все найденные результаты
            'max_parse' => 5,                  // Максимум для парсинга
            'skip_blacklisted' => true,        // Пропускать черный список
            'timeout' => 30,                    // Общий таймаут
            'search_options' => [],              // Опции для поиска
            'parse_options' => []                // Опции для парсера
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
                'position' => $result['position'] ?? null
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
            
            if ($parseResult['success']) {
                $parseStats['success']++;
                $this->stats['successful_parses']++;
                $parsedResults[] = [
                    'url' => $item['url'],
                    'title' => $item['title'],
                    'domain' => $item['domain'],
                    'position' => $item['position'],
                    'parsed' => $parseResult
                ];
            } else {
                $parseStats['failed']++;
                $this->stats['failed_parses']++;
                writeLog("Parse failed for {$item['url']}: " . ($parseResult['error'] ?? 'Unknown'), 'controller');
                
                // Опционально: сохраняем информацию о неудаче
                $parsedResults[] = [
                    'url' => $item['url'],
                    'title' => $item['title'],
                    'domain' => $item['domain'],
                    'position' => $item['position'],
                    'error' => $parseResult['error'] ?? 'Parse failed',
                    'http_code' => $parseResult['http_code'] ?? null
                ];
            }
            
            // Небольшая задержка между запросами
            usleep(300000); // 0.3 сек
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
            'search_stats' => $this->search->getStats ? $this->search->getStats() : null,
            'parser_stats' => $this->parser->getStats(),
            'blacklist' => $this->blacklist->getAll()
        ];
    }
    
    /**
     * Очистить кэш
     */
    public function clearCache() {
        $this->search->clearCache();
        $this->parser->clearCache();
        writeLog("Controller: all caches cleared", 'controller');
    }
}
?>