<?php
// find_bot.php — ищет инициализацию Telegram-бота и версию библиотеки
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

$root = __DIR__;
echo "Корень: $root\n\n";

// 1. Версия библиотеки
echo "=== composer.lock (фрагмент про telegram-bot/api) ===\n";
$lock = @file_get_contents($root . '/composer.lock');
if ($lock) {
    if (preg_match('/"name":\s*"telegram-bot\/api".*?"version":\s*"([^"]+)"/s', $lock, $m)) {
        echo "Версия: " . $m[1] . "\n";
    } else {
        echo "Пакет telegram-bot/api не найден в composer.lock\n";
    }
} else {
    echo "composer.lock не найден\n";
}

// 2. Какие классы есть в библиотеке
echo "\n=== Структура vendor/telegram-bot/api/src/ ===\n";
$dir = $root . '/vendor/telegram-bot/api/src';
if (is_dir($dir)) {
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        echo ($f === 'Http' ? '[DIR] ' : '      ') . $f . "\n";
    }
} else {
    echo "Директория не найдена: $dir\n";
}

// 3. Поиск файлов, где создаётся Client
echo "\n=== Файлы, где есть 'new Client' или 'new \\TelegramBot\\Api\\Client' ===\n";
$skip = array('vendor', '.git', 'node_modules');
$found = array();

function scan($dir, $root, $skip, &$found) {
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $path = $dir . '/' . $f;
        if (is_dir($path)) {
            if (in_array($f, $skip)) continue;
            scan($path, $root, $skip, $found);
        } elseif (preg_match('/\.php$/i', $f)) {
            $c = @file_get_contents($path);
            if ($c !== false && preg_match('/new\s+(\\\\?TelegramBot\\\\Api\\\\)?Client\s*\(/', $c)) {
                $found[] = str_replace($root, '', $path);
            }
        }
    }
}
scan($root, $root, $skip, $found);

if ($found) {
    foreach ($found as $p) echo $p . "\n";
} else {
    echo "Не найдено\n";
}

// 4. Ищем любой входной файл (webhook, index, run, start)
echo "\n=== Возможные точки входа ===\n";
$candidates = array('index.php','bot.php','webhook.php','hook.php','run.php','start.php','main.php','app.php');
foreach ($candidates as $c) {
    if (file_exists($root . '/' . $c)) echo "/" . $c . "\n";
}

// 5. Проверяем наличие GuzzleHttpClient (3.x) или CurlHttpClient
echo "\n=== Классы HTTP-клиента ===\n";
foreach (array(
    'TelegramBot\\Api\\Http\\GuzzleHttpClient',
    'TelegramBot\\Api\\Http\\CurlHttpClient',
    'TelegramBot\\Api\\Http\\StreamHttpClient',
) as $c) {
    if (file_exists($root . '/vendor/' . str_replace('\\', '/', $c) . '.php')) {
        echo "Найден: $c\n";
    }
}
echo "\nГотово.\n";