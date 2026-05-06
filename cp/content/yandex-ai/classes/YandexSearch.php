<?php
// /fordzap.com/cp/content/yandex-ai/classes/YandexSearch.php

require_once __DIR__ . '/CacheManager.php';
require_once __DIR__ . '/BlacklistManager.php';

class YandexSearch {
    private $folderId;
    private $apiKey;
    private $cache;
    private $blacklist;
    private $lastError;
    private $lastResponse;
    
    public function __construct() {
        $this->folderId = FOLDER_ID;
        $this->apiKey = API_KEY;
        $this->cache = new CacheManager('responses');
        $this->blacklist = new BlacklistManager();
        
        writeLog("YandexSearch initialized", 'search');
    }
    
    /**
     * Поиск с автоматической заменой заблокированных доменов
     */
    public function search($query, $options = []) {
        writeLog("Searching for: $query", 'search');
        
        $defaults = [
            'max_results' => SEARCH_MAX_RESULTS,
            'page' => 0,
            'region' => SEARCH_REGION,
            'use_cache' => SEARCH_USE_CACHE,
            'cache_ttl' => SEARCH_CACHE_TTL,
            'fix_typo' => SEARCH_FIX_TYPO,
            'family_mode' => SEARCH_FAMILY_MODE,
            'timeout' => SEARCH_TIMEOUT,
            'sort' => 'relevance',
            'filter_blacklist' => true,
            'max_pages' => 3 // Максимум страниц для дозапроса
        ];
        
        $options = array_merge($defaults, $options);
        
        $cacheKey = $this->buildCacheKey($query, $options);
    
		// Проверяем кэш, но с дополнительной проверкой черного списка
		if ($options['use_cache']) {
			$cached = $this->cache->get($cacheKey, $options['cache_ttl']);
			if ($cached && isset($cached['results'])) {
				// Проверяем, нет ли в кэше заблокированных доменов
				$hasBlacklisted = false;
				foreach ($cached['results'] as $result) {
					$domain = $this->extractDomain($result['url']);
					if ($this->blacklist->isBlacklisted($domain)) {
						$hasBlacklisted = true;
						break;
					}
				}
				
				// Если в кэше есть заблокированные - игнорируем кэш
				if (!$hasBlacklisted) {
					writeLog("Returning cached results for: $query", 'search');
					return $cached;
				} else {
					writeLog("Cache contains blacklisted domains, refetching", 'search');
				}
			}
		}
        
        // Собираем результаты с нескольких страниц
        $allResults = [];
        $currentPage = $options['page'];
        $pagesFetched = 0;
        $blacklistedSkipped = 0;
        
        while (count($allResults) < $options['max_results'] && $pagesFetched < $options['max_pages']) {
            writeLog("Fetching page $currentPage, need " . ($options['max_results'] - count($allResults)) . " more results", 'search');
            
            $requestData = $this->buildRequest($query, array_merge($options, ['page' => $currentPage]));
            $response = $this->makeRequest($requestData);
            
            if (!$response) {
                break;
            }
            
            $pageResults = $this->parseResponse($response);
            
            if (empty($pageResults['results'])) {
                break;
            }
            
            // Фильтруем черный список на этой странице
            $filteredPageResults = [];
            foreach ($pageResults['results'] as $result) {
                $domain = $this->extractDomain($result['url']);
                
                if (!$this->blacklist->isBlacklisted($domain)) {
                    $filteredPageResults[] = $result;
                } else {
                    $blacklistedSkipped++;
                    writeLog("Skipped blacklisted domain: $domain", 'search');
                }
            }
            
            // Добавляем отфильтрованные результаты
            $allResults = array_merge($allResults, $filteredPageResults);
            
            $currentPage++;
            $pagesFetched++;
            
            // Если не набрали нужное количество, продолжаем на следующей странице
        }
        
        // Обрезаем до нужного количества
        $finalResults = array_slice($allResults, 0, $options['max_results']);
        
        $result = [
            'success' => true,
            'query' => $query,
            'total_found' => count($allResults), // Реальное количество после фильтрации
            'total_available' => count($allResults) + $blacklistedSkipped, // Сколько было до фильтрации
            'results_count' => count($finalResults),
            'results' => $finalResults,
            'filtered' => [
                'skipped_blacklisted' => $blacklistedSkipped,
                'pages_fetched' => $pagesFetched,
                'requested' => $options['max_results']
            ]
        ];
        
        if ($options['use_cache']) {
            $this->cache->set($cacheKey, $result);
        }
        
        writeLog("Search completed: {$result['results_count']} results after filtering ($blacklistedSkipped blacklisted skipped)", 'search');
        
        return $result;
    }
    
    /**
     * Извлечение домена из URL
     */
    private function extractDomain($url) {
        $domain = parse_url($url, PHP_URL_HOST);
        if (!$domain) return '';
        
        // Убираем www
        $domain = preg_replace('/^www\./', '', $domain);
        return strtolower(trim($domain));
    }
    
    /**
     * Сформировать ключ для кэша
     */
    private function buildCacheKey($query, $options) {
        $key = $query . '_' . 
               $options['max_results'] . '_' . 
               $options['region'] . '_' . 
               $options['sort'];
        
        return 'search_' . md5($key);
    }
    
    /**
     * Сформировать запрос к API
     */
    private function buildRequest($query, $options) {
        $sortMode = 'SORT_MODE_BY_RELEVANCE';
        if ($options['sort'] === 'time') {
            $sortMode = 'SORT_MODE_BY_TIME';
        }
        
        return [
            'query' => [
                'searchType' => 'SEARCH_TYPE_RU',
                'queryText' => $query,
                'familyMode' => $options['family_mode'],
                'page' => (int)$options['page'],
                'fixTypoMode' => $options['fix_typo'] ? 'FIX_TYPO_MODE_ON' : 'FIX_TYPO_MODE_OFF'
            ],
            'sortSpec' => [
                'sortMode' => $sortMode,
                'sortOrder' => 'SORT_ORDER_DESC'
            ],
            'groupSpec' => [
                'groupMode' => 'GROUP_MODE_FLAT',
                'groupsOnPage' => $options['max_results'],
                'docsInGroup' => 1
            ],
            'maxPassages' => 5,
            'region' => $options['region'],
            'l10N' => 'LOCALIZATION_RU',
            'folderId' => $this->folderId,
            'responseFormat' => 'FORMAT_XML'
        ];
    }
    
    /**
     * Выполнить HTTP запрос к API
     */
    private function makeRequest($data) {
        $url = 'https://searchapi.api.cloud.yandex.net/v2/web/search';
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Api-Key ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            $this->lastError = 'CURL Error: ' . curl_error($ch);
            writeLog($this->lastError, 'error');
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $this->lastError = "HTTP Error: $httpCode";
            writeLog($this->lastError, 'error');
            
            $errorData = json_decode($response, true);
            if (isset($errorData['message'])) {
                $this->lastError .= ' - ' . $errorData['message'];
            }
            
            return false;
        }
        
        $this->lastResponse = $response;
        return $response;
    }
    
    /**
     * Парсинг XML ответа
     */
    private function parseResponse($response) {
        $data = json_decode($response, true);
        
        if (!isset($data['rawData'])) {
            writeLog("Invalid API response: missing rawData", 'error');
            return [
                'success' => false,
                'error' => 'Invalid API response format',
                'results' => []
            ];
        }
        
        $xmlData = base64_decode($data['rawData']);
        $xml = simplexml_load_string($xmlData);
        
        if (!$xml) {
            writeLog("Failed to parse XML response", 'error');
            return [
                'success' => false,
                'error' => 'Failed to parse XML',
                'results' => []
            ];
        }
        
        $namespaces = $xml->getNamespaces(true);
        $yandexNs = $namespaces['yandex'] ?? null;
        
        if ($yandexNs) {
            $xml->registerXPathNamespace('y', $yandexNs);
        }
        
        $docs = $this->extractDocs($xml);
        
        $results = [];
        foreach ($docs as $doc) {
            $result = $this->parseDoc($doc);
            if ($result) {
                $results[] = $result;
            }
        }
        
        return [
            'success' => true,
            'results' => $results
        ];
    }
    
    /**
     * Извлечь все документы из XML
     */
    private function extractDocs($xml) {
        $docs = [];
        
        $paths = [
            '//doc',
            '//group/doc',
            '//response/results/grouping/group/doc'
        ];
        
        foreach ($paths as $path) {
            $found = $xml->xpath($path);
            if (!empty($found)) {
                return $found;
            }
        }
        
        $this->findDocsRecursive($xml, $docs);
        
        return $docs;
    }
    
    /**
     * Рекурсивный поиск документов
     */
    private function findDocsRecursive($node, &$docs) {
        if ($node->getName() === 'doc') {
            $docs[] = $node;
        }
        
        foreach ($node->children() as $child) {
            $this->findDocsRecursive($child, $docs);
        }
    }
    
    /**
     * Парсинг одного документа
     */
    private function parseDoc($doc) {
        $url = (string)$doc->url;
        
        if (empty($url)) {
            return null;
        }
        
        $title = (string)$doc->title;
        $title = strip_tags($title);
        
        $domain = (string)$doc->domain;
        if (empty($domain)) {
            $domain = parse_url($url, PHP_URL_HOST);
        }
        
        $snippet = '';
        if (isset($doc->passages)) {
            foreach ($doc->passages->children() as $passage) {
                $snippet .= ' ' . strip_tags((string)$passage);
            }
        }
        $snippet = trim($snippet);
        
        $properties = [];
        if (isset($doc->properties)) {
            foreach ($doc->properties->children() as $prop) {
                $name = $prop->getName();
                $value = trim((string)$prop);
                if (!empty($value)) {
                    $properties[$name] = $value;
                }
            }
        }
        
        return [
            'url' => $url,
            'title' => $title,
            'domain' => $domain,
            'snippet' => $snippet,
            'properties' => $properties
        ];
    }
    
    /**
     * Получить последний сырой ответ
     */
    public function getLastRawResponse() {
        return $this->lastResponse;
    }
    
    /**
     * Получить последнюю ошибку
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Очистить кэш поиска
     */
    public function clearCache() {
        $this->cache->clear();
        writeLog("Search cache cleared", 'search');
    }
    
    /**
     * Получить черный список
     */
    public function getBlacklist() {
        return $this->blacklist->getAll();
    }
}
?>