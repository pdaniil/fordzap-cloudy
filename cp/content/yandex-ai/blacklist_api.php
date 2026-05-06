<?php
// /fordzap.com/cp/content/yandex-ai/blacklist_api.php

require_once __DIR__ . '/config.php';
require_once CLASSES_DIR . '/BlacklistManager.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE, OPTIONS');
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
    
    $action = $input['action'] ?? 'list';
    $domain = $input['domain'] ?? '';
    $reason = $input['reason'] ?? 'manual';
    
    writeLog("Blacklist API request: action=$action, domain=$domain", 'blacklist');
    
    $blacklist = new BlacklistManager();
    
    switch ($action) {
        case 'list':
            echo json_encode([
                'success' => true,
                'data' => $blacklist->getAll()
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;
            
        case 'add':
            if (empty($domain)) {
                throw new Exception('Domain is required');
            }
            
            $details = $input['details'] ?? [];
            $result = $blacklist->add($domain, $reason, $details);
            
            echo json_encode([
                'success' => true,
                'added' => $result,
                'data' => $blacklist->getAll()
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;
            
        case 'remove':
            if (empty($domain)) {
                throw new Exception('Domain is required');
            }
            
            $result = $blacklist->remove($domain);
            
            echo json_encode([
                'success' => true,
                'removed' => $result,
                'data' => $blacklist->getAll()
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;
            
        case 'clear':
            $blacklist->clear();
            
            echo json_encode([
                'success' => true,
                'message' => 'Blacklist cleared'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            throw new Exception("Unknown action: $action");
    }
    
} catch (Exception $e) {
    writeLog("Blacklist API error: " . $e->getMessage(), 'error');
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>