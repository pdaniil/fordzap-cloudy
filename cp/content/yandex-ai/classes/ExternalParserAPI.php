<?php
// /fordzap.com/cp/content/yandex-ai/classes/ExternalParserAPI.php

class ExternalParserAPI {
    private $baseUrl = 'https://api.fordzap.com';
    private $timeout = 120;
    private $maxAttempts = 60;
    private $pollDelay = 1;
    
    public function health() {
        return $this->request('GET', '/health');
    }
    
    public function systemStats() {
        return $this->request('GET', '/api/system');
    }
    
    public function parseSingle($url, $options = array()) {
        $waitFor = isset($options['wait_for']) ? $options['wait_for'] : 1000;
        $noCache = isset($options['no_cache']) ? $options['no_cache'] : false;
        
        $data = array(
            'url' => $url,
            'wait_for' => 5000,
            'no_cache' => $noCache
        );
        
        writeLog("parseSingle: sending request to /api/parse with data: " . json_encode($data), 'debug');
        
        $result = $this->request('POST', '/api/parse', $data);
        writeLog("parseSingle: request result: " . json_encode($result), 'debug');
        
        if (!isset($result['task_id'])) {
            writeLog("ExternalParserAPI: parseSingle failed - no task_id in response", 'error');
            return null;
        }
        
        writeLog("parseSingle: waiting for task {$result['task_id']}", 'debug');
        $taskResult = $this->waitForResult($result['task_id']);
        writeLog("parseSingle: taskResult: " . json_encode($taskResult), 'debug');
        
        if ($taskResult && $taskResult['status'] == 'completed') {
            if (isset($taskResult['result'])) {
                return $taskResult['result'];
            }
        }
        
        writeLog("ExternalParserAPI: parseSingle failed - task status not completed or no result", 'error');
        return null;
    }
    
    public function parseBatch($urls, $parallel = 5, $waitFor = 1000, $noCache = false) {
        $data = array(
            'urls' => $urls,
            'parallel' => $parallel,
            'wait_for' => $waitFor,
            'no_cache' => $noCache
        );
        
        $result = $this->request('POST', '/api/parse-batch', $data);
        if (!isset($result['task_id'])) {
            writeLog("ExternalParserAPI: parseBatch failed - no task_id", 'error');
            return null;
        }
        
        $taskResult = $this->waitForResult($result['task_id']);
        if ($taskResult && $taskResult['status'] == 'completed') {
            if (isset($taskResult['result']['results'])) {
                return $taskResult['result'];
            }
        }
        
        writeLog("ExternalParserAPI: parseBatch failed - task status not completed", 'error');
        return null;
    }
    
    private function waitForResult($taskId) {
        for ($i = 0; $i < $this->maxAttempts; $i++) {
            $result = $this->request('GET', "/api/task/$taskId");
            if ($result && isset($result['status'])) {
                if ($result['status'] == 'completed' || $result['status'] == 'failed') {
                    return $result;
                }
            }
            sleep($this->pollDelay);
        }
        return null;
    }
    
    public function clearCache() {
        return $this->request('DELETE', '/api/cache');
    }
    
    private function request($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        
        if ($data !== null) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            writeLog("ExternalParserAPI: Sending $method $url with data", 'debug');
        } else {
            writeLog("ExternalParserAPI: Sending $method $url", 'debug');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        if ($response === false) {
            writeLog("ExternalParserAPI: cURL error for $url: $error", 'error');
            curl_close($ch);
            return null;
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            writeLog("ExternalParserAPI: HTTP $httpCode for $url", 'error');
            if ($response) {
                writeLog("ExternalParserAPI: Response body: " . substr($response, 0, 500), 'debug');
            }
            return null;
        }
        
        $decoded = json_decode($response, true);
        if ($decoded === null) {
            writeLog("ExternalParserAPI: Invalid JSON for $url", 'error');
            writeLog("ExternalParserAPI: Response: " . substr($response, 0, 500), 'debug');
            return null;
        }
        
        return $decoded;
    }
}
?>