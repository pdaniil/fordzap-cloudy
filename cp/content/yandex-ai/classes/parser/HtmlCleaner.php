<?php
/**
 * Класс HtmlCleaner
 * 
 * ОТВЕТСТВЕННОСТЬ:
 * Очистка HTML от мусора и извлечение полезного текста.
 * 
 * ЗАЧЕМ НУЖЕН:
 * - Удалять скрипты, стили, навигацию
 * - Извлекать заголовки и мета-теги
 * - Превращать HTML в чистый текст для анализа
 */

class HtmlCleaner {
    
    /**
     * Очистить HTML и извлечь полезную информацию
     * 
     * @param string $html Исходный HTML
     * @param string $url URL страницы (для контекста)
     * @param array $options Опции очистки
     * @return array Результат с полями:
     *         - success: bool успех
     *         - url: string исходный URL
     *         - title: string|null заголовок
     *         - description: string|null описание
     *         - keywords: string|null ключевые слова
     *         - text: string очищенный текст
     *         - stats: array статистика
     */
    public function clean($html, $url, $options) {
        $result = [
            'success' => true,
            'url' => $url,
            'title' => null,
            'description' => null,
            'keywords' => null,
            'text' => null,
            'stats' => []
        ];
        
        // 1. Определяем и конвертируем кодировку
        $analyzer = new HtmlAnalyzer();  // Временно создаем анализатор
        $encoding = $analyzer->detectEncoding($html);
        if ($encoding && $encoding !== 'UTF-8') {
            $html = mb_convert_encoding($html, 'UTF-8', $encoding);
        }
        
        // 2. Извлекаем мета-данные
        if ($options['extract_title']) {
            $result['title'] = $this->extractTitle($html);
        }
        if ($options['extract_description']) {
            $result['description'] = $this->extractMeta($html, 'description');
        }
        if ($options['extract_keywords']) {
            $result['keywords'] = $this->extractMeta($html, 'keywords');
        }
        
        // 3. Очищаем HTML и получаем текст
        if ($options['clean_html']) {
            $result['text'] = $this->cleanText($html, $options);
        } else {
            $result['html'] = $html;  // Если не нужно очищать, возвращаем как есть
        }
        
        return $result;
    }
    
    /**
     * Извлечь заголовок страницы из <title>
     * 
     * @param string $html HTML
     * @return string|null
     */
    private function extractTitle($html) {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return trim(strip_tags($matches[1]));
        }
        return null;
    }
    
    /**
     * Извлечь мета-тег по имени
     * 
     * @param string $html HTML
     * @param string $name Имя мета-тега (description, keywords)
     * @return string|null
     */
    private function extractMeta($html, $name) {
        $pattern = '/<meta[^>]*name=["\']' . $name . '["\'][^>]*content=["\']([^"\']*)["\']/is';
        if (preg_match($pattern, $html, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
    
    /**
     * Очистить HTML и получить чистый текст
     * Последовательно удаляет:
     * - Скрипты
     * - Стили
     * - Навигацию (nav, footer, header)
     * - Комментарии
     * - HTML теги
     * 
     * @param string $html HTML
     * @param array $options Опции очистки
     * @return string
     */
    private function cleanText($html, $options) {
        $text = $html;
        
        // Удаляем скрипты
        if ($options['remove_scripts']) {
            $text = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $text);
        }
        
        // Удаляем стили
        if ($options['remove_styles']) {
            $text = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $text);
            $text = preg_replace('/\s*style\s*=\s*"[^"]*"/i', '', $text);
        }
        
        // Удаляем навигационные элементы
        if ($options['remove_navigation']) {
            $text = preg_replace('/<nav\b[^>]*>(.*?)<\/nav>/is', '', $text);
            $text = preg_replace('/<footer\b[^>]*>(.*?)<\/footer>/is', '', $text);
            $text = preg_replace('/<header\b[^>]*>(.*?)<\/header>/is', '', $text);
        }
        
        // Удаляем комментарии
        $text = preg_replace('/<!--.*?-->/s', '', $text);
        
        // Удаляем все HTML теги
        $text = strip_tags($text);
        
        // Декодируем HTML сущности (&nbsp; и т.д.)
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        // Убираем лишние пробелы и переносы строк
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Если текст пустой после всех манипуляций, пробуем запасной метод
        if (empty($text)) {
            $text = $this->fallbackExtractText($html);
        }
        
        // Ограничиваем длину, если нужно
        if (strlen($text) > $options['max_length']) {
            $text = mb_substr($text, 0, $options['max_length'], 'UTF-8') . '...';
        }
        
        return $text;
    }
    
    /**
     * Запасной метод извлечения текста
     * Просто удаляет все теги, оставляя текст между ними
     * 
     * @param string $html HTML
     * @return string
     */
    private function fallbackExtractText($html) {
        $text = preg_replace('/<[^>]*>/', ' ', $html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
?>