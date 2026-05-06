<?php
require_once $_SERVER["DOCUMENT_ROOT"] . '/cp/content/cloudflare_parser/config.php';

class CloudflareBrowser {
    private $accountId;
    private $apiToken;
    private $baseUrl = 'https://api.cloudflare.com/client/v4';
    private $lastError;
    
    public function __construct($accountId = null, $apiToken = null) {
        if ($accountId === null || $apiToken === null) {
            $config = loadCloudflareConfig();
            $this->accountId = $config['account_id'];
            $this->apiToken = $config['api_token'];
        } else {
            $this->accountId = $accountId;
            $this->apiToken = $apiToken;
        }
    }
    
    /**
     * Получить HTML страницы
     * @param string $url URL для загрузки
     * @param array $options Дополнительные параметры API:
     *      - gotoOptions: array (timeout, waitUntil)
     *      - viewport: array (width, height)
     *      - rejectResourceTypes: array (image, stylesheet, script и т.д.)
     */
    public function fetchHtml($url, $options = []) {
        $endpoint = $this->baseUrl . '/accounts/' . $this->accountId . '/browser-rendering/content';
        
        // Базовый payload с URL
        $payload = ['url' => $url];
        
        // Добавляем опции если есть
        if (!empty($options)) {
            // Рекурсивно объединяем массивы
            $payload = $this->arrayMerge($payload, $options);
        }
        
        $response = $this->makeRequest($endpoint, $payload);
        
        if ($response && isset($response['content'])) {
            return $response['content'];
        }
        
        return false;
    }
    
    /**
     * Получить Markdown страницы
     */
    public function fetchMarkdown($url, $options = []) {
        $endpoint = $this->baseUrl . '/accounts/' . $this->accountId . '/browser-rendering/markdown';
        
        $payload = ['url' => $url];
        
        if (!empty($options)) {
            $payload = $this->arrayMerge($payload, $options);
        }
        
        $response = $this->makeRequest($endpoint, $payload);
        
        if ($response && isset($response['content'])) {
            return $response['content'];
        }
        
        return false;
    }
    
    /**
     * Отправить запрос к API
     */
    private function makeRequest($endpoint, $payload) {
        $ch = curl_init($endpoint);
        
        $jsonPayload = json_encode($payload);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiToken,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_TIMEOUT => 70, // Увеличиваем до 70 секунд
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Cloudflare-PHP-Parser/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        // Логируем для отладки
        error_log("Cloudflare API Response: HTTP $httpCode, Size: " . ($info['size_download'] ?? 0) . " bytes");
        
        if ($error) {
            $this->lastError = "cURL Error: $error";
            return false;
        }
        
        if ($httpCode !== 200) {
            $data = json_decode($response, true);
            $errorMsg = isset($data['errors'][0]['message']) ? $data['errors'][0]['message'] : "HTTP Error: $httpCode";
            $this->lastError = "API Error: $errorMsg";
            return false;
        }
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['success']) || !$data['success']) {
            $errorMsg = isset($data['errors'][0]['message']) ? $data['errors'][0]['message'] : 'Unknown error';
            $this->lastError = "API Error: $errorMsg";
            return false;
        }
        
        return $data['result'] ?? false;
    }
    
    /**
     * Проверка соединения
     */
    public function testConnection() {
        $endpoint = $this->baseUrl . '/accounts/' . $this->accountId;
        
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->apiToken],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    /**
     * Получить последнюю ошибку
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Рекурсивное объединение массивов
     */
    private function arrayMerge($array1, $array2) {
        $result = $array1;
        
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                $result[$key] = $this->arrayMerge($result[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
}
?>