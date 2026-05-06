БЫСТРЫЙ СТАРТ
=============

1. Подключение в коде:
------------------------
require_once 'classes/PageParser.php';
$parser = new PageParser();
$result = $parser->parse('https://example.com/page');

2. Структура результата:
------------------------
{
    "success": true,
    "url": "https://...",
    "title": "Заголовок страницы",
    "description": "Мета-описание",
    "text": "Очищенный текст страницы...",
    "stats": {
        "content_length": 12345,
        "used_cloudflare": false,
        "parser_stats": {...}
    }
}

3. Обработка ошибок:
------------------------
if (!$result['success']) {
    echo "Ошибка: " . $result['error'];
}

4. Кастомизация опций:
------------------------
$result = $parser->parse($url, [
    'timeout' => 15,
    'use_cache' => false,
    'remove_navigation' => true
]);

5. Где что лежит:
------------------------
classes/PageParser.php              - главный класс
classes/parser/HttpFetcher.php      - загрузка страниц
classes/parser/HtmlAnalyzer.php     - анализ HTML
classes/parser/HtmlCleaner.php      - очистка HTML
classes/parser/ErrorHandler.php     - обработка ошибок
classes/parser/CloudflareHandler.php - Cloudflare интеграция