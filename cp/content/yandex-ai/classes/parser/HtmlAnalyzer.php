<?php
/**
 * Класс HtmlAnalyzer
 * 
 * ОТВЕТСТВЕННОСТЬ:
 * Анализ HTML без его изменения.
 * Определяет тип страницы, JS-фреймворки, блокировки.
 * 
 * ЗАЧЕМ НУЖЕН:
 * - Принимать решение, нужно ли использовать Cloudflare
 * - Определять, стоит ли добавлять домен в черный список
 * - Понимать, что за страница перед нами
 */

class HtmlAnalyzer {
    
    /**
     * Определить, использует ли страница JS-фреймворк
     * 
     * @param string $html HTML страницы
     * @return string|null Название фреймворка или null
     */
    public function detectJsFramework($html) {
        if (empty($html)) return null;
        
        // Сначала проверяем, есть ли вообще контент
        // Если текста много, то фреймворк не важен - страница рабочая
        $text = strip_tags($html);
        if (strlen($text) > 500) {
            return null;
        }
        
        // Проверяем характерные признаки разных фреймворков
        if (strpos($html, 'window.__NUXT__') !== false) return 'Nuxt.js';
        if (strpos($html, 'window.__VUE__') !== false || strpos($html, 'data-v-') !== false) return 'Vue.js';
        if (strpos($html, 'window.__REACT__') !== false || strpos($html, 'react-dom') !== false) return 'React';
        if (strpos($html, 'window.__NEXT__') !== false) return 'Next.js';
        if (strpos($html, 'ng-version') !== false) return 'Angular';
        
        return null;
    }
    
    /**
     * Проверить, является ли страница пустой JS-страницей
     * Такие страницы содержат только JS-код и CSS, без полезного контента
     * 
     * @param string $html HTML страницы
     * @return bool
     */
    public function isEmptyJsPage($html) {
        if (empty($html)) return false;
        
        // Получаем чистый текст без HTML тегов
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Если есть текст - скорее всего не пустая
        if (strlen($text) > 100) return false;
        
        // Считаем соотношение JS-кода к HTML-тегам
        $jsCount = substr_count($html, '<script');
        $htmlCount = substr_count($html, '<div') + substr_count($html, '<span') + 
                     substr_count($html, '<p') + substr_count($html, '<a');
        
        // Если JS много, а HTML мало - вероятно пустая JS-страница
        if ($jsCount > 2 && $htmlCount < 5 && strlen($text) < 50) return true;
        
        // Проверяем специфичные паттерны пустых страниц
        $emptyPatterns = [
            '/\.horizontal-vertical\s*{[^}]*}/',  // CSS для прелоадера
            '/setup_utm\s*\(/',                     // UTM функции
            '/get_utm_/',                            // UTM функции
            '/document\.referrer/',                   // Работа с реферером
            '/window\.__NUXT__/',                     // Nuxt.js
            '/<body[^>]*>\s*<script/',                // Тело начинается со скрипта
        ];
        
        $matches = 0;
        foreach ($emptyPatterns as $pattern) {
            if (preg_match($pattern, $html)) $matches++;
        }
        
        // Если нашли несколько признаков И мало текста
        return ($matches >= 2 && strlen($text) < 200);
    }
    
    /**
     * Проверить, похоже ли на SPA (Single Page Application)
     * SPA сайты часто не отдают готовый HTML, а грузят всё через JS
     * 
     * @param string $html HTML страницы
     * @return bool
     */
    public function isLikelySpa($html) {
        if (empty($html)) return false;
        
        $spaIndicators = [
            '<div id="app"',           // Vue/React стандарт
            '<div id="root"',           // React
            '<div id="__next"',         // Next.js
            '<div id="___gatsby"',       // Gatsby
            'data-reactroot',           // React
            'ng-version',               // Angular
            'vue-version',              // Vue
            'window.__NUXT__',          // Nuxt
            'window.__VUE__',           // Vue
            'window.__REACT__',          // React
            'window.__NEXT__'            // Next
        ];
        
        foreach ($spaIndicators as $indicator) {
            if (strpos($html, $indicator) !== false) return true;
        }
        
        return false;
    }
    
    /**
     * Проверить, не заблокировали ли доступ
     * Многие сайты возвращают специальные страницы с капчей или запретом
     * 
     * @param string $html HTML страницы
     * @return bool
     */
    public function isBlocked($html) {
        $blockPatterns = [
            '/captcha/i',                // Капча
            '/access denied/i',           // Доступ запрещен
            '/access blocked/i',           // Доступ заблокирован
            '/403 Forbidden/i',            // HTTP 403
            '/404 Not Found/i',            // HTTP 404
            '/too many requests/i',        // Слишком много запросов
            '/rate limit/i',               // Лимит запросов
            '/please wait/i',              // Пожалуйста, подождите
            '/just a moment/i',            // Подождите момент
            '/checking your browser/i',    // Проверка браузера
            '/ddos protection/i',           // DDoS защита
            '/attention required/i',        // Требуется внимание
            '/security check/i'             // Проверка безопасности
        ];
        
        foreach ($blockPatterns as $pattern) {
            if (preg_match($pattern, $html)) return true;
        }
        
        return false;
    }
    
    /**
     * Определить кодировку страницы
     * Нужно для правильного преобразования в UTF-8
     * 
     * @param string $html HTML страницы
     * @return string|null Название кодировки или null
     */
    public function detectEncoding($html) {
        // Ищем в meta-теге charset
        if (preg_match('/<meta[^>]*charset=["\']?([a-zA-Z0-9-]+)["\']?/i', $html, $matches)) {
            return strtoupper($matches[1]);
        }
        // Ищем в XML-декларации
        if (preg_match('/^<\?xml[^>]+encoding=["\']([^"\']+)["\']/i', $html, $matches)) {
            return strtoupper($matches[1]);
        }
        return null;
    }
}
?>