<?php
// /fordzap.com/cp/content/cloudflare-parser/config.php

/**
 * Конфигурационный файл для Cloudflare Parser
 * Версия для PHP 5.4
 */

// Загружаем конфиг из JSON файла
$configFile = $_SERVER["DOCUMENT_ROOT"] . '/cp/content/cloudflare_parser/config.json';

if (!file_exists($configFile)) {
    die('config.json not found in cloudflare-parser. Please create config.json file with account_id and api_token');
}

$jsonContent = file_get_contents($configFile);
$config = json_decode($jsonContent, true);

if (!$config || !is_array($config)) {
    die('Invalid config.json in cloudflare-parser. Please check JSON format.');
}

// Проверяем наличие необходимых полей
if (!isset($config['account_id']) || !isset($config['api_token'])) {
    die('config.json must contain account_id and api_token fields');
}

// Определяем константы для PHP 5.4
define('CLOUDFLARE_ACCOUNT_ID', $config['account_id']);
define('CLOUDFLARE_API_TOKEN', $config['api_token']);
define('CLOUDFLARE_BASE_URL', 'https://api.cloudflare.com/client/v4');

// Опции по умолчанию
define('CLOUDFLARE_TIMEOUT', 30);
define('CLOUDFLARE_VIEWPORT_WIDTH', 1920);
define('CLOUDFLARE_VIEWPORT_HEIGHT', 1080);

// Функция для загрузки конфига (на случай если нужно получить массив)
function loadCloudflareConfig() {
    return array(
        'account_id' => CLOUDFLARE_ACCOUNT_ID,
        'api_token' => CLOUDFLARE_API_TOKEN,
        'base_url' => CLOUDFLARE_BASE_URL,
        'timeout' => CLOUDFLARE_TIMEOUT,
        'viewport' => array(
            'width' => CLOUDFLARE_VIEWPORT_WIDTH,
            'height' => CLOUDFLARE_VIEWPORT_HEIGHT
        )
    );
}

// Функция для проверки конфигурации
function checkCloudflareConfig() {
    $required = array('CLOUDFLARE_ACCOUNT_ID', 'CLOUDFLARE_API_TOKEN');
    $missing = array();
    
    foreach ($required as $constant) {
        if (!defined($constant)) {
            $missing[] = $constant;
        }
    }
    
    if (!empty($missing)) {
        return array(
            'valid' => false,
            'missing' => $missing,
            'message' => 'Missing required constants: ' . implode(', ', $missing)
        );
    }
    
    return array(
        'valid' => true,
        'message' => 'Cloudflare configuration is valid'
    );
}

// Пример содержимого config.json для справки:
/*
{
    "account_id": "your-cloudflare-account-id",
    "api_token": "your-cloudflare-api-token"
}
*/

// Логирование загрузки конфига
if (defined('CLOUDFLARE_DEBUG') && CLOUDFLARE_DEBUG) {
    error_log("[CloudflareParser] Config loaded successfully");
    error_log("[CloudflareParser] Account ID: " . substr(CLOUDFLARE_ACCOUNT_ID, 0, 8) . "...");
}

?>