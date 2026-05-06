<?php
// /fordzap.com/cp/content/yandex-ai/classes/PageParser.php

require_once __DIR__ . '/ExternalParserAPI.php';
require_once __DIR__ . '/CacheManager.php';
require_once __DIR__ . '/BlacklistManager.php';

class PageParser {
    private $api;
    private $cache;
    private $blacklist;
    private $stats;
    
    public function __construct() {
        $this->api = new ExternalParserAPI();
        $this->cache = new CacheManager('pages');
        $this->blacklist = new BlacklistManager();
        
        $this->stats = array(
            'requests' => 0,
            'success' => 0,
            'failed' => 0,
            'cached' => 0,
            'blacklisted' => 0,
            'captcha' => 0
        );
        
        writeLog("PageParser initialized (using external API)", 'parser');
    }
    
    public function parse($url, $options = array()) {
        $this->stats['requests']++;
        writeLog("Parsing URL: $url", 'parser');
        
        $domain = parse_url($url, PHP_URL_HOST);
        $domain = $this->normalizeDomain($domain);
        
        if ($this->blacklist->isBlacklisted($domain)) {
            $this->stats['blacklisted']++;
            writeLog("Domain $domain is blacklisted, skipping", 'parser');
            return $this->errorResult($url, 'Domain is blacklisted');
        }
        
        $defaults = array(
            'use_cache' => true,
            'wait_for' => 1000,
            'no_cache' => false
        );
        $options = array_merge($defaults, $options);
        
        if ($options['use_cache']) {
            $cacheKey = 'page_' . md5($url);
            $cached = $this->cache->get($cacheKey, 86400);
            if ($cached) {
                $this->stats['cached']++;
                $cached['cached'] = true;
                writeLog("Returning cached content for: $url", 'parser');
                return $cached;
            }
        }
        
        $apiResult = $this->api->parseSingle($url, array(
            'wait_for' => $options['wait_for'],
            'no_cache' => $options['no_cache']
        ));
        
        if (!$apiResult) {
            $this->stats['failed']++;
            return $this->errorResult($url, 'API request failed');
        }
        
        // Определяем новый формат
        $isNewFormat = isset($apiResult['overall_structured']) || isset($apiResult['selling_description']);
        $hasCaptcha = false;
        
        if ($isNewFormat && isset($apiResult['urls_status']) && is_array($apiResult['urls_status'])) {
            foreach ($apiResult['urls_status'] as $status) {
                if (($status['url'] ?? '') === $url && ($status['parse_status'] ?? '') === 'captcha') {
                    $hasCaptcha = true;
                    break;
                }
            }
        }
        
        if (!$hasCaptcha && isset($apiResult['status']) && $apiResult['status'] === 'captcha') {
            $hasCaptcha = true;
        }
        
        if ($hasCaptcha) {
            $this->stats['captcha']++;
            $this->blacklist->add($domain, 'captcha', array(
                'type' => $apiResult['captcha_type'] ?? 'unknown',
                'url' => $url
            ));
            return $this->errorResult($url, 'Captcha detected');
        }
        
        if (isset($apiResult['error'])) {
            $this->stats['failed']++;
            $this->blacklist->add($domain, 'parse_failed', array(
                'error' => $apiResult['error'],
                'url' => $url
            ));
            return $this->errorResult($url, $apiResult['error']);
        }
        
        $this->stats['success']++;
        
        $structured = $isNewFormat ? ($apiResult['overall_structured'] ?? []) : [];
        $sellingDescription = $isNewFormat ? ($apiResult['selling_description'] ?? '') : '';
        $urlsStatus = $isNewFormat ? ($apiResult['urls_status'] ?? []) : [];
        
        $html = $apiResult['html'] ?? '';
        $title = '';
        $description = '';
        $text = '';
        
        if ($html) {
            if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
                $title = trim(strip_tags($matches[1]));
            }
            if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\']/is', $html, $matches)) {
                $description = trim($matches[1]);
            }
            $text = $html;
            $text = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $text);
            $text = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $text);
            $text = preg_replace('/<nav\b[^>]*>(.*?)<\/nav>/is', '', $text);
            $text = preg_replace('/<footer\b[^>]*>(.*?)<\/footer>/is', '', $text);
            $text = preg_replace('/<header\b[^>]*>(.*?)<\/header>/is', '', $text);
            $text = strip_tags($text);
            $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
        }
        
        if (!empty($structured['description'])) {
            $description = $structured['description'];
        }
        
        $result = array(
            'success' => true,
            'url' => $url,
            'title' => $title,
            'description' => $description,
            'text' => $text,
            'raw_html' => $html,
            'cached' => false,
            'stats' => array(
                'size' => $apiResult['size'] ?? 0,
                'time' => $apiResult['processing_time'] ?? 0
            ),
            'api_result' => $apiResult,
            'overall_structured' => $structured,
            'selling_description' => $sellingDescription,
            'urls_status' => $urlsStatus
        );
        
        if ($options['use_cache']) {
            $cacheKey = 'page_' . md5($url);
            $this->cache->set($cacheKey, $result);
        }
        
        return $result;
    }
    
   public function parseBatch($urls, $options = array()) {
		if (empty($urls)) {
			return array('success' => false, 'error' => 'No URLs provided');
		}
		
		if (!is_array($urls)) {
			$urls = array($urls);
		}
		
		$defaults = array(
			'use_cache' => true,
			'parallel' => 5,
			'wait_for' => 1000,
			'no_cache' => false
		);
		$options = array_merge($defaults, $options);
		
		$results = array();
		$urlsToParse = array();
		
		foreach ($urls as $url) {
			$this->stats['requests']++;
			$domain = parse_url($url, PHP_URL_HOST);
			$domain = $this->normalizeDomain($domain);
			
			if ($this->blacklist->isBlacklisted($domain)) {
				$this->stats['blacklisted']++;
				$results[$url] = $this->errorResult($url, 'Domain is blacklisted');
				continue;
			}
			
			if ($options['use_cache']) {
				$cacheKey = 'page_' . md5($url);
				$cached = $this->cache->get($cacheKey, 86400);
				if ($cached) {
					$this->stats['cached']++;
					$cached['cached'] = true;
					$results[$url] = $cached;
					continue;
				}
			}
			
			$urlsToParse[] = $url;
		}
		
		$globalStructured = array();
		$globalSelling = '';
		$globalUrlsStatus = array();
		
		if (!empty($urlsToParse)) {
			$apiResult = $this->api->parseBatch($urlsToParse, $options['parallel'], $options['wait_for'], $options['no_cache']);
			
			if ($apiResult && isset($apiResult['results'])) {
				// Определяем новый формат: есть ли overall_structured на верхнем уровне
				$isNewFormat = isset($apiResult['overall_structured']) || isset($apiResult['selling_description']);
				
				if ($isNewFormat) {
					$globalStructured = $apiResult['overall_structured'] ?? array();
					$globalSelling = $apiResult['selling_description'] ?? '';
					$globalUrlsStatus = $apiResult['urls_status'] ?? array();
				}
				
				foreach ($apiResult['results'] as $item) {
					$url = $item['url'];
					$domain = parse_url($url, PHP_URL_HOST);
					$domain = $this->normalizeDomain($domain);
					
					// Проверка на капчу из глобального списка или из item
					$hasCaptcha = false;
					if ($isNewFormat && !empty($globalUrlsStatus)) {
						foreach ($globalUrlsStatus as $status) {
							if (($status['url'] ?? '') === $url && ($status['parse_status'] ?? '') === 'captcha') {
								$hasCaptcha = true;
								break;
							}
						}
					}
					if (!$hasCaptcha && isset($item['status']) && $item['status'] === 'captcha') {
						$hasCaptcha = true;
					}
					
					if ($hasCaptcha) {
						$this->stats['captcha']++;
						$this->blacklist->add($domain, 'captcha', array(
							'type' => $item['captcha_type'] ?? 'unknown',
							'url' => $url
						));
						$results[$url] = $this->errorResult($url, 'Captcha detected');
						continue;
					}
					
					if (isset($item['error'])) {
						$this->stats['failed']++;
						$this->blacklist->add($domain, 'parse_failed', array(
							'error' => $item['error'],
							'url' => $url
						));
						$results[$url] = $this->errorResult($url, $item['error']);
						continue;
					}
					
					$this->stats['success']++;
					$html = $item['html'] ?? '';
					
					$title = '';
					$description = '';
					$text = '';
					if ($html) {
						if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
							$title = trim(strip_tags($matches[1]));
						}
						if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\']/is', $html, $matches)) {
							$description = trim($matches[1]);
						}
						$text = $html;
						$text = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $text);
						$text = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $text);
						$text = preg_replace('/<nav\b[^>]*>(.*?)<\/nav>/is', '', $text);
						$text = preg_replace('/<footer\b[^>]*>(.*?)<\/footer>/is', '', $text);
						$text = preg_replace('/<header\b[^>]*>(.*?)<\/header>/is', '', $text);
						$text = strip_tags($text);
						$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
						$text = preg_replace('/\s+/', ' ', $text);
						$text = trim($text);
					}
					
					// Для каждого элемента используем общие структурированные данные, если они есть
					$structured = $globalStructured;
					$selling = $globalSelling;
					
					// Если нет общего, возможно, в item есть свои (старый формат)
					if (empty($structured) && isset($item['overall_structured'])) {
						$structured = $item['overall_structured'];
						$selling = $item['selling_description'] ?? '';
					}
					
					if (!empty($structured['description'])) {
						$description = $structured['description'];
					}
					
					$resultItem = array(
						'success' => true,
						'url' => $url,
						'title' => $title,
						'description' => $description,
						'text' => $text,
						'raw_html' => $html,
						'cached' => false,
						'stats' => array(
							'size' => $item['size'] ?? 0,
							'time' => $item['processing_time'] ?? 0
						),
						'api_result' => $item,
						'overall_structured' => $structured,
						'selling_description' => $selling,
						'urls_status' => $globalUrlsStatus
					);
					
					if ($options['use_cache']) {
						$cacheKey = 'page_' . md5($url);
						$this->cache->set($cacheKey, $resultItem);
					}
					
					$results[$url] = $resultItem;
				}
			} else {
				foreach ($urlsToParse as $url) {
					$this->stats['failed']++;
					$results[$url] = $this->errorResult($url, 'Batch API request failed');
				}
			}
		}
		
		$orderedResults = array();
		foreach ($urls as $url) {
			$orderedResults[$url] = isset($results[$url]) ? $results[$url] : $this->errorResult($url, 'Unknown error');
		}
		
		$response = array(
			'success' => true,
			'results' => $orderedResults,
			'stats' => $this->stats
		);
		
		// Добавляем глобальные структурированные данные, если они были получены
		if (!empty($globalStructured)) {
			$response['overall_structured'] = $globalStructured;
			$response['selling_description'] = $globalSelling;
			$response['urls_status'] = $globalUrlsStatus;
		}
		
		return $response;
	}
    
    private function normalizeDomain($domain) {
        if (!$domain) return '';
        $domain = preg_replace('/^www\./', '', $domain);
        return strtolower(trim($domain));
    }
    
    private function errorResult($url, $error, $httpCode = null) {
        $result = array(
            'success' => false,
            'url' => $url,
            'error' => $error,
            'stats' => $this->stats
        );
        if ($httpCode) {
            $result['http_code'] = $httpCode;
        }
        return $result;
    }
    
    public function getStats() {
        return $this->stats;
    }
    
    public function clearCache() {
        $this->cache->clear();
        writeLog("Parser cache cleared", 'parser');
    }
}
?>