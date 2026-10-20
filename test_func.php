<?php
// ============================================================
//  Тест подключения к Telegram Bot API через прокси
//  PHP 5.4+
//  - Логин/пароль передаются отдельно (CURLOPT_PROXYUSERPWD)
//  - Расшифровка частых ошибок cURL
//  - Проверка доступа без авторизации (whitelist IP)
//  - Поддержка SOCKS5 / HTTP
// ============================================================

// ================= НАСТРОЙКИ ПО УМОЛЧАНИЮ =================
$defaultHost = '188.221.105.87';
$defaultPort = 10000;
$defaultUser = 'RiWPM0L6lSTb7YuDF15c';
$defaultPass = 'Wn85wxDPzmtjlpyQasJi';
// =========================================================

// ---- Читаем параметры формы ----
$proxyHost = isset($_GET['host'])    ? trim($_GET['host'])    : $defaultHost;
$proxyPort = isset($_GET['port'])    ? (int)$_GET['port']     : $defaultPort;
$proxyUser = isset($_GET['user'])    ? trim($_GET['user'])    : $defaultUser;
$proxyPass = isset($_GET['pass'])    ? $_GET['pass']          : $defaultPass;
$token     = isset($_GET['token'])   ? trim($_GET['token'])   : '';
$proto     = isset($_GET['proto'])   ? $_GET['proto']         : 'socks5'; // socks5 | http
$authMode  = isset($_GET['auth'])    ? $_GET['auth']          : 'creds';  // creds | noauth
$timeout   = isset($_GET['timeout']) ? (int)$_GET['timeout']  : 20;

if ($timeout < 1)  { $timeout = 1; }
if ($timeout > 60) { $timeout = 60; }

// ---- Окружение ----
ini_set('display_errors', '1');
error_reporting(E_ALL);

$logs = array();

function logMsg($level, $message) {
    global $logs;
    $logs[] = array('time' => date('H:i:s'), 'level' => $level, 'message' => $message);
}

function logRender() {
    global $logs;
    echo '<div style="font-family: monospace; background:#111; color:#eee; padding:12px; border-radius:6px; white-space:pre-wrap; word-break:break-all; line-height:1.5;">';
    foreach ($logs as $log) {
        switch ($log['level']) {
            case 'ERROR':   $c = '#ff6b6b'; break;
            case 'WARN':    $c = '#ffd93d'; break;
            case 'INFO':    $c = '#6bcbff'; break;
            case 'SUCCESS': $c = '#6bff6b'; break;
            default:        $c = '#eeeeee'; break;
        }
        echo '<div style="color:' . $c . ';">[' . $log['time'] . '] ' . htmlspecialchars($log['level']) . ': ' . htmlspecialchars($log['message']) . '</div>';
    }
    echo '</div>';
}

/**
 * Расшифровка кодов ошибок cURL
 */
function curlErrorHint($errno) {
    switch ($errno) {
        case 5:  return 'Не удалось разрешить хост прокси (проверьте адрес прокси).';
        case 6:  return 'Не удалось разрешить хост назначения.';
        case 7:  return 'Не удалось подключиться к прокси (порт закрыт или IP недоступен).';
        case 28: return 'Таймаут. Прокси не пропускает запрос.';
        case 35: return 'Ошибка SSL-рукопожатия (TLS handshake).';
        case 56: return 'Ошибка при приёме данных от прокси.';
        case 97: return 'Прокси отклонил логин/пароль (SOCKS5 User rejected). Проверьте креденшелы или IP-whitelist.';
        default: return '';
    }
}

/**
 * Универсальный cURL-запрос через прокси
 */
function curlRequest($url, $proxyUrl, $proxyType, $proxyAuth, $timeout) {
    $ch      = curl_init();
    $verbose = fopen('php://temp', 'w+');

    $options = array(
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_VERBOSE        => true,
        CURLOPT_STDERR         => $verbose,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_USERAGENT      => 'PHP Telegram Proxy Test',
    );

    if ($proxyUrl !== '') {
        $options[CURLOPT_PROXY]     = $proxyUrl;
        $options[CURLOPT_PROXYTYPE] = $proxyType;
        if ($proxyAuth !== '') {
            $options[CURLOPT_PROXYUSERPWD] = $proxyAuth;
            $options[CURLOPT_HTTPAUTH]     = CURLAUTH_ANY; // для HTTP-прокси
        }
    }

    curl_setopt_array($ch, $options);

    logMsg('INFO', 'Запрос: ' . $url);
    $start    = microtime(true);
    $response = curl_exec($ch);
    $duration = round((microtime(true) - $start) * 1000, 2);

    $info  = curl_getinfo($ch);
    $error = curl_error($ch);
    $errno = curl_errno($ch);

    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    fclose($verbose);
    curl_close($ch);

    logMsg('INFO', "cURL verbose:\n" . $verboseLog);

    if ($errno) {
        logMsg('ERROR', "cURL ошибка ($errno): $error");
        $hint = curlErrorHint($errno);
        if ($hint !== '') {
            logMsg('WARN', 'Подсказка: ' . $hint);
        }
    } elseif ($info['http_code']) {
        logMsg('SUCCESS', "HTTP код: {$info['http_code']}, время: {$duration} мс, connect: {$info['connect_time']} с");
    } else {
        logMsg('ERROR', "HTTP код: 0, время: {$duration} мс");
    }

    $headerSize = $info['header_size'];
    return array(
        'info'     => $info,
        'headers'  => substr($response, 0, $headerSize),
        'body'     => substr($response, $headerSize),
        'error'    => $error,
        'errno'    => $errno,
        'verbose'  => $verboseLog,
        'duration' => $duration,
    );
}

// ---- Собираем параметры прокси ----
if ($proto === 'http') {
    $proxyUrl  = 'http://' . $proxyHost . ':' . $proxyPort;
    $proxyType = CURLPROXY_HTTP;
} else {
    $proxyUrl  = 'socks5h://' . $proxyHost . ':' . $proxyPort; // socks5h = DNS через прокси
    $proxyType = CURLPROXY_SOCKS5;
    if (defined('CURLPROXY_SOCKS5_HOSTNAME')) {
        $proxyType = CURLPROXY_SOCKS5_HOSTNAME;
    }
}

$proxyAuth = ($authMode === 'creds') ? ($proxyUser . ':' . $proxyPass) : '';

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Тест Telegram через прокси</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; color: #222; }
        h1 { color: #333; }
        .section { background: #fff; padding: 15px; margin-bottom: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; border-radius: 4px; }
        label { display: inline-block; margin-right: 12px; }
        input[type=text], input[type=number] { padding: 4px 6px; }
        button { padding: 6px 14px; cursor: pointer; }
        .info-block { background: #eef6ff; border-left: 4px solid #6bcbff; padding: 8px 12px; margin: 10px 0; }
        .row { margin-bottom: 8px; }
    </style>
</head>
<body>
<h1>Тест подключения к Telegram Bot API через прокси</h1>

<form method="get" class="section">
    <div class="row">
        <label>Хост: <input type="text" name="host" value="<?php echo htmlspecialchars($proxyHost); ?>" size="20"></label>
        <label>Порт: <input type="text" name="port" value="<?php echo htmlspecialchars((string)$proxyPort); ?>" size="8"></label>
        <label>Протокол:
            <select name="proto">
                <option value="socks5" <?php if ($proto === 'socks5') echo 'selected'; ?>>SOCKS5</option>
                <option value="http"   <?php if ($proto === 'http')   echo 'selected'; ?>>HTTP</option>
            </select>
        </label>
    </div>
    <div class="row">
        <label>Логин: <input type="text" name="user" value="<?php echo htmlspecialchars($proxyUser); ?>" size="30"></label>
        <label>Пароль: <input type="text" name="pass" value="<?php echo htmlspecialchars($proxyPass); ?>" size="30"></label>
    </div>
    <div class="row">
        <label>Авторизация:
            <select name="auth">
                <option value="creds"  <?php if ($authMode === 'creds')  echo 'selected'; ?>>Логин/пароль</option>
                <option value="noauth" <?php if ($authMode === 'noauth') echo 'selected'; ?>>Без авторизации (whitelist IP)</option>
            </select>
        </label>
        <label>Таймаут (сек): <input type="number" name="timeout" value="<?php echo (int)$timeout; ?>" min="1" max="60"></label>
    </div>
    <div class="row">
        <label>Токен бота (опционально): <input type="text" name="token" value="<?php echo htmlspecialchars($token); ?>" size="50"></label>
    </div>
    <button type="submit">Запустить тест</button>
</form>

<div class="section">
    <h2>Результат</h2>
    <?php
    logMsg('INFO', '=== Начало теста ===');
    logMsg('INFO', "Прокси-сервер: {$proxyHost}:{$proxyPort}");
    logMsg('INFO', "Протокол: {$proto}, авторизация: {$authMode}, таймаут: {$timeout} с");
    if ($proxyAuth !== '') {
        logMsg('INFO', 'Логин: ' . $proxyUser . ', длина пароля: ' . strlen($proxyPass) . ' симв.');
    } else {
        logMsg('WARN', 'Режим без авторизации — используется IP-whitelist');
    }

    // ---- Шаг 1: базовый эндпоинт api.telegram.org ----
    logMsg('INFO', '--- Шаг 1: https://api.telegram.org ---');
    $res1 = curlRequest('https://api.telegram.org', $proxyUrl, $proxyType, $proxyAuth, $timeout);

    echo '<h3>Шаг 1: https://api.telegram.org</h3>';
    echo '<p>HTTP код: <b>' . htmlspecialchars((string)$res1['info']['http_code']) . '</b> '
       . '| Время: ' . htmlspecialchars((string)$res1['duration']) . ' мс</p>';
    if ($res1['errno']) {
        echo '<p style="color:#c00">Ошибка (' . (int)$res1['errno'] . '): ' . htmlspecialchars($res1['error']) . '</p>';
        $hint = curlErrorHint($res1['errno']);
        if ($hint !== '') {
            echo '<p style="color:#a60">' . htmlspecialchars($hint) . '</p>';
        }
    }
    echo '<pre>' . htmlspecialchars(substr($res1['body'], 0, 1500)) . '</pre>';

    // ---- Шаг 2: getMe, если есть токен ----
    if ($token !== '') {
        logMsg('INFO', '--- Шаг 2: getMe ---');
        $url2 = 'https://api.telegram.org/bot' . $token . '/getMe';
        $res2 = curlRequest($url2, $proxyUrl, $proxyType, $proxyAuth, $timeout);

        echo '<h3>Шаг 2: getMe</h3>';
        echo '<p>HTTP код: <b>' . htmlspecialchars((string)$res2['info']['http_code']) . '</b> '
           . '| Время: ' . htmlspecialchars((string)$res2['duration']) . ' мс</p>';
        if ($res2['errno']) {
            echo '<p style="color:#c00">Ошибка (' . (int)$res2['errno'] . '): ' . htmlspecialchars($res2['error']) . '</p>';
        }
        echo '<pre>' . htmlspecialchars($res2['body']) . '</pre>';

        $json = json_decode($res2['body'], true);
        if (is_array($json) && isset($json['ok']) && $json['ok'] === true) {
            $uname = isset($json['result']['username']) ? $json['result']['username'] : '?';
            logMsg('SUCCESS', 'Бот успешно авторизован: @' . $uname);
        } elseif (is_array($json) && isset($json['description'])) {
            logMsg('ERROR', 'Telegram вернул ошибку: ' . $json['description']);
        }
    } else {
        logMsg('WARN', 'Токен не указан — шаг 2 (getMe) пропущен');
    }

    // ---- Итог ----
    if ((int)$res1['info']['http_code'] === 200) {
        logMsg('SUCCESS', '=== ПРОКСИ РАБОТАЕТ: соединение с Telegram установлено ===');
        echo '<div class="info-block" style="background:#eaffea; border-left-color:#6bff6b;">';
        echo '<b>Итог:</b> прокси работает, соединение с api.telegram.org установлено.';
        echo '</div>';
    } else {
        logMsg('ERROR', '=== ПРОКСИ НЕ РАБОТАЕТ: соединение не установлено ===');
        echo '<div class="info-block" style="background:#ffeeee; border-left-color:#ff6b6b;">';
        echo '<b>Итог:</b> прокси не пропускает запросы. Смотрите подсказку и полный лог ниже.';
        echo '</div>';
    }

    logMsg('INFO', '=== Тест завершён ===');

    echo '<h3>Полный лог</h3>';
    logRender();
    ?>
</div>

</body>
</html>