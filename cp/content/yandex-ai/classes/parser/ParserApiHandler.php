<?php

// Подключение класса PageParser (если нужно)
// require_once 'PageParser.php';

/**
 * Обработчик API запросов для парсера
 */
class ParserApiHandler {
    private $parser;
    private $input;
    
    public function __construct() {
        $this->parser = new PageParser();
        $this->input = $this->getInput();
    }
    
    public function handle() {
        $action = $this->input['action'] ?? 'parse';
        
        $methodName = 'action' . ucfirst($action);
        if (!method_exists($this, $methodName)) {
            throw new Exception("Unknown action: $action", 400);
        }
        
        return $this->$methodName();
    }
    
    private function actionParse() {
        $url = isset($this->input['url']) ? $this->input['url'] : '';
        $this->validateUrl($url);
        
        $options = $this->buildOptions();
        $result = $this->parser->parse($url, $options);
        
        $result['request'] = array(
            'url' => $url,
            'options' => $options,
            'timestamp' => time()
        );
        
        return $result;
    }
    
    private function actionParseMultiple() {
        $urls = isset($this->input['urls']) ? $this->input['urls'] : array();
        $validUrls = $this->validateUrls($urls);
        $options = $this->buildOptions();
        
        $results = array();
        $successCount = 0;
        $failCount = 0;
        
        foreach ($validUrls as $url) {
            try {
                $parseResult = $this->parser->parse($url, $options);
                
                // Проверка успеха для PHP 5.4
                if (isset($parseResult['success']) && $parseResult['success'] === true) {
                    $successCount++;
                } else {
                    $failCount++;
                }
                
                $results[$url] = $parseResult;
            } catch (Exception $e) {
                $failCount++;
                $results[$url] = array(
                    'success' => false,
                    'error' => $e->getMessage()
                );
            }
            
            usleep(300000);
        }
        
        return array(
            'success' => true,
            'results' => $results,
            'stats' => array(
                'total' => count($validUrls),
                'success' => $successCount,
                'failed' => $failCount,
                'parser_stats' => $this->parser->getStats(),
                'cloudflare_stats' => $this->parser->getCloudflareStats()
            ),
            'request' => array(
                'urls_count' => count($validUrls),
                'options' => $options,
                'timestamp' => time()
            )
        );
    }
    
    private function actionStats() {
        return array(
            'success' => true,
            'stats' => $this->parser->getStats(),
            'cloudflare' => $this->parser->getCloudflareStats()
        );
    }
    
    private function actionClearCache() {
        $this->parser->clearCache();
        return array('success' => true, 'message' => 'Cache cleared');
    }
    
    private function getInput() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = file_get_contents('php://input');
            $decoded = json_decode($input, true);
            return is_array($decoded) ? $decoded : array();
        }
        return $_GET;
    }
    
    private function validateUrl($url) {
        if (empty($url)) {
            throw new Exception('URL parameter is required', 400);
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL format: ' . $url, 400);
        }
    }
    
    private function validateUrls($urls) {
        if (empty($urls)) {
            throw new Exception('URLs array is required', 400);
        }
        
        if (!is_array($urls)) {
            $urls = array($urls);
        }
        
        // Исправлено для PHP 5.4
        $valid = array_filter($urls, function($u) {
            return filter_var($u, FILTER_VALIDATE_URL);
        });
        
        if (empty($valid)) {
            throw new Exception('No valid URLs provided', 400);
        }
        
        return $valid;
    }
    
    private function buildOptions() {
        return array(
            'use_cache' => isset($this->input['use_cache']) 
                ? (bool)$this->input['use_cache'] 
                : (defined('PARSER_USE_CACHE') ? PARSER_USE_CACHE : true),
            'timeout' => isset($this->input['timeout']) 
                ? (int)$this->input['timeout'] 
                : (defined('PARSER_TIMEOUT') ? PARSER_TIMEOUT : 30)
        );
    }
}