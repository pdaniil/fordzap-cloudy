<?php
ini_set("display_errors", 1);

/**
 * Класс CloudflareHandler
 * 
 * ОТВЕТСТВЕННОСТЬ:
 * Работа с Cloudflare Browser Rendering API для загрузки сложных JS-сайтов.
 * 
 * ЗАЧЕМ НУЖЕН:
 * - Загружать страницы, которые не отдают готовый HTML
 * - Получать Markdown версию страниц
 * - Делать скриншоты для отладки
 * - Работать как fallback для обычного CURL
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/cp/content/cloudflare_parser/CloudflareBrowser.php';


class CloudflareHandler {
    /**
     * @var CloudflareBrowser Экземпляр Cloudflare клиента
     */
    private $cloudflare;
    
    /**
     * @var array Статистика использования
     */
    private $stats = array('used' => 0, 'success' => 0);
    
    /**
     * Конструктор - пытается инициализировать Cloudflare клиент
     */
    public function __construct() {
        try {
            // Проверяем, существует ли файл с классом
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/cp/content/cloudflare_parser/CloudflareBrowser.php')) {
                $this->cloudflare = new CloudflareBrowser();
                $this->writeLog("Cloudflare Browser initialized", 'parser');
            } else {
				
                $this->writeLog("Cloudflare Browser not available (file not found)", 'warning');
            }
        } catch (Exception $e) {
			
            $this->writeLog("Cloudflare Browser not available: " . $e->getMessage(), 'warning');
        }
    }
    
    /**
     * Проверить, доступен ли Cloudflare
     * 
     * @return bool
     */
    public function isAvailable() {
        return $this->cloudflare !== null;
    }
    
    /**
     * Загрузить страницу через Cloudflare
     * Сначала пробует Markdown (дает чистый текст), затем HTML
     * 
     * @param string $url URL для загрузки
     * @param array $options Опции
     * @return string|null HTML или Markdown контент
     */
    public function fetch($url, $options) {
        if (!$this->cloudflare) return null;
        
        $this->stats['used']++;
        
        // 1. Пробуем получить Markdown (самый чистый вариант)
        try {
            $content = $this->cloudflare->fetchMarkdown($url);
            // Проверяем, что контент не пустой
            if ($content && strlen($content) > 100) {
                $this->stats['success']++;
                $this->writeLog("Cloudflare Markdown success, length: " . strlen($content), 'debug');
                return $content;
            }
        } catch (Exception $e) {
            $this->writeLog("Cloudflare Markdown failed: " . $e->getMessage(), 'debug');
        }
        
        // 2. Если Markdown не сработал, пробуем HTML
        try {
            $viewport_width = defined('CLOUDFLARE_VIEWPORT_WIDTH') ? CLOUDFLARE_VIEWPORT_WIDTH : 1920;
            $viewport_height = defined('CLOUDFLARE_VIEWPORT_HEIGHT') ? CLOUDFLARE_VIEWPORT_HEIGHT : 1080;
            $timeout = defined('CLOUDFLARE_TIMEOUT') ? CLOUDFLARE_TIMEOUT : 30;
            
            $html = $this->cloudflare->fetchHtml($url, array(
                'viewport' => array(
                    'width' => $viewport_width,
                    'height' => $viewport_height
                ),
                'timeout' => isset($options['timeout']) ? $options['timeout'] : $timeout
            ));
            
            if ($html && strlen($html) > 100) {
                $this->stats['success']++;
                $this->writeLog("Cloudflare HTML success, length: " . strlen($html), 'debug');
                return $html;
            }
        } catch (Exception $e) {
            $this->writeLog("Cloudflare HTML failed: " . $e->getMessage(), 'debug');
        }
        
        return null;
    }
    
    /**
     * Получить статистику использования Cloudflare
     * 
     * @return array
     */
    public function getStats() {
        return $this->stats;
    }
    
    /**
     * Запись в лог (если функция writeLog не определена глобально)
     * 
     * @param string $message Сообщение
     * @param string $type Тип лога
     */
    private function writeLog($message, $type = 'info') {
        // Проверяем, существует ли глобальная функция writeLog
        if (function_exists('writeLog')) {
            writeLog($message, $type);
        } else {
            // Если нет, пишем в error_log
            error_log("[CloudflareHandler][$type] $message");
        }
    }
}