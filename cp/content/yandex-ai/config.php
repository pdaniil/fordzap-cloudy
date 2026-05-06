<?php
// /fordzap.com/cp/content/yandex-ai/config.php
error_reporting(0);

// Читаем конфиг из JSON
$configFile = __DIR__ . '/config.json';
if (!file_exists($configFile)) {
    die('config.json not found');
}

$config = json_decode(file_get_contents($configFile), true);
if (!$config) {
    die('Invalid config.json');
}


// ===== НАСТРОЙКИ БАЗЫ ДАННЫХ =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'm71automai');
define('DB_USER', 'm71automai');      // замените на реального пользователя
define('DB_PASS', 'xJZi7KTTJK2BPkM_');            // замените на реальный пароль

// ===== ПУТИ =====


// ===== ОБЩИЕ НАСТРОЙКИ =====
define('FOLDER_ID', $config['folder_id']);
define('API_KEY', $config['api_key']);

// ===== НАСТРОЙКИ ПОИСКА =====
define('SEARCH_MAX_RESULTS', $config['search']['max_results']);
define('SEARCH_MAX_PAGES', $config['search']['max_pages']);
define('SEARCH_REGION', $config['search']['region']);
define('SEARCH_TIMEOUT', $config['search']['timeout']);
define('SEARCH_USE_CACHE', $config['search']['use_cache']);
define('SEARCH_CACHE_TTL', $config['search']['cache_ttl']);
define('SEARCH_FIX_TYPO', $config['search']['fix_typo']);
define('SEARCH_FAMILY_MODE', $config['search']['family_mode']);

// ===== НАСТРОЙКИ ПАРСЕРА =====
define('PARSER_MAX_URLS', $config['parser']['max_urls_per_request']);
define('PARSER_TIMEOUT', $config['parser']['timeout']);
define('PARSER_MAX_REDIRECTS', $config['parser']['max_redirects']);
define('PARSER_USE_CACHE', $config['parser']['use_cache']);
define('PARSER_CACHE_TTL', $config['parser']['cache_ttl']);
define('PARSER_MIN_LENGTH', $config['parser']['min_content_length']);
define('PARSER_MAX_LENGTH', $config['parser']['max_content_length']);
define('PARSER_CLEAN_HTML', $config['parser']['clean_html']);
define('PARSER_REMOVE_SCRIPTS', $config['parser']['remove_scripts']);
define('PARSER_REMOVE_STYLES', $config['parser']['remove_styles']);
define('PARSER_REMOVE_NAV', $config['parser']['remove_navigation']);
define('PARSER_EXTRACT_TITLE', $config['parser']['extract_title']);
define('PARSER_EXTRACT_DESC', $config['parser']['extract_description']);
define('PARSER_EXTRACT_KEYWORDS', $config['parser']['extract_keywords']);
define('PARSER_DELAY', $config['parser']['delay_between_requests']);

// ===== НАСТРОЙКИ ЧЕРНОГО СПИСКА =====
define('BLACKLIST_ENABLED', $config['blacklist']['enabled']);
define('BLACKLIST_AUTO_ADD', $config['blacklist']['auto_add']);
define('BLACKLIST_MAX_ATTEMPTS', $config['blacklist']['max_attempts']);

// ===== НАСТРОЙКИ КЭША =====
define('CACHE_ENABLED', $config['cache']['enabled']);
define('CACHE_SEARCH_TTL', $config['cache']['search_ttl']);
define('CACHE_PAGE_TTL', $config['cache']['page_ttl']);
define('CACHE_MAX_SIZE', $config['cache']['max_size_mb'] * 1024 * 1024);

// ===== НАСТРОЙКИ ЛОГИРОВАНИЯ =====
define('LOG_MAX_LINES', $config['logging']['max_lines']);
define('LOG_ERRORS', $config['logging']['log_errors']);
define('LOG_INFO', $config['logging']['log_info']);
define('LOG_DEBUG', $config['logging']['log_debug']);

// ===== ПУТИ =====
define('CACHE_DIR', __DIR__ . '/cache');
define('CLASSES_DIR', __DIR__ . '/classes');
define('MODULES_DIR', __DIR__);



define('OAUTH_TOKEN', $config['oauth_token']);

// Проверяем папки
$requiredDirs = [
    CACHE_DIR,
    CACHE_DIR . '/responses',
    CACHE_DIR . '/pages',
    CACHE_DIR . '/logs'
];

foreach ($requiredDirs as $dir) {
    if (!file_exists($dir)) {
        die("Required directory does not exist: $dir");
    }
    if (!is_dir($dir)) {
        die("Path exists but is not a directory: $dir");
    }
}

// Определяем имя текущего скрипта для лога
$scriptName = basename($_SERVER['SCRIPT_NAME'], '.php');
if (empty($scriptName)) {
    $scriptName = 'cli_' . getmypid();
}

define('CURRENT_LOG_FILE', CACHE_DIR . '/logs/' . $scriptName . '.log');

// Проверяем существование лог-файла
if (!file_exists(CURRENT_LOG_FILE)) {
    die("Log file does not exist: " . CURRENT_LOG_FILE . ". Please create it manually.");
}

if (!is_writable(CURRENT_LOG_FILE)) {
    die("Log file is not writable: " . CURRENT_LOG_FILE);
}

// Функция логирования
function writeLog($message, $type = 'info') {
    if ($type === 'error' && !LOG_ERRORS) return;
    if ($type === 'info' && !LOG_INFO) return;
    if ($type === 'debug' && !LOG_DEBUG) return;
    
    $logFile = CURRENT_LOG_FILE;
    $timestamp = date('Y-m-d H:i:s');
    
    if (!file_exists($logFile)) {
        return false;
    }
    
    $logMessage = "[$timestamp] [$type] $message\n";
    
    // Ротация при превышении лимита
    $lines = file($logFile, FILE_IGNORE_NEW_LINES);
    if ($lines !== false && count($lines) >= LOG_MAX_LINES) {
        $lines = array_slice($lines, -(int)(LOG_MAX_LINES * 0.8));
        file_put_contents($logFile, implode("\n", $lines) . "\n", LOCK_EX);
        file_put_contents($logFile, "[$timestamp] [system] Log rotated\n", FILE_APPEND | LOCK_EX);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    return true;
}

function getIamToken() {
    $cacheFile = CACHE_DIR . '/iam_token.txt';
    
    // Проверяем кэш (11 часов)
    if (file_exists($cacheFile)) {
        $content = file_get_contents($cacheFile);
        $parts = explode('|', $content);
        if (count($parts) === 2) {
            list($token, $expires) = $parts;
            if (time() < (int)$expires) {
                return $token;
            }
        }
    }
    
    $url = 'https://iam.api.cloud.yandex.net/iam/v1/tokens';
    $data = json_encode(['yandexPassportOauthToken' => OAUTH_TOKEN]);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['iamToken'])) {
            $expires = time() + 3600 * 11;
            file_put_contents($cacheFile, $result['iamToken'] . '|' . $expires);
            return $result['iamToken'];
        }
    }
    
    throw new Exception("Ошибка получения IAM токена");
}

writeLog("Config initialized for script: $scriptName", 'system');
?>