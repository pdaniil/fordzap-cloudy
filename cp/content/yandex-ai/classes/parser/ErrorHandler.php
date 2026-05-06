<?php
/**
 * Класс ErrorHandler
 * 
 * ОТВЕТСТВЕННОСТЬ:
 * Обработка ошибок загрузки и добавление проблемных доменов в черный список.
 * 
 * ЗАЧЕМ НУЖЕН:
 * - Централизованная обработка всех ошибок
 * - Классификация ошибок по типам
 * - Автоматическое добавление в черный список
 * - Логирование проблем
 */

class ErrorHandler {
    /**
     * @var BlacklistManager Ссылка на менеджер черного списка
     */
    private $blacklist;
    
    /**
     * Конструктор
     * 
     * @param BlacklistManager $blacklist Менеджер черного списка
     */
    public function __construct($blacklist) {
        $this->blacklist = $blacklist;
    }
    
    /**
     * Обработать ошибку загрузки
     * 
     * @param string $domain Домен, на котором произошла ошибка
     * @param string $url Полный URL
     * @param string $error Текст ошибки
     * @param int $httpCode HTTP код ответа (если есть)
     * @param array $options Опции запроса
     * @param array &$stats Ссылка на статистику для обновления
     */
    public function handle($domain, $url, $error, $httpCode, $options, &$stats) {
        // 1. Таймауты - сайт не отвечает
        if (strpos($error, 'Operation timed out') !== false) {
            $stats['timeouts']++;
            $this->blacklist->add($domain, 'timeout', [
                'url' => $url,
                'error' => $error,
                'timeout' => $options['timeout']
            ]);
        }
        // 2. SSL ошибки - проблемы с сертификатами
        elseif (strpos($error, 'SSL') !== false || 
                strpos($error, 'SSL_ERROR_SYSCALL') !== false ||
                strpos($error, 'SSL connection') !== false) {
            $stats['ssl_errors'] = ($stats['ssl_errors'] ?? 0) + 1;
            $this->blacklist->add($domain, 'ssl_error', [
                'url' => $url,
                'error' => $error
            ]);
        }
        // 3. HTTP ошибки - разные коды обрабатываем по-разному
        elseif ($httpCode >= 400) {
            $stats['http_errors']++;
            $this->handleHttpError($domain, $url, $error, $httpCode);
        }
    }
    
    /**
     * Обработать HTTP ошибки с разными кодами
     * 
     * @param string $domain Домен
     * @param string $url URL
     * @param string $error Текст ошибки
     * @param int $httpCode HTTP код
     */
    private function handleHttpError($domain, $url, $error, $httpCode) {
        switch ($httpCode) {
            case 403:
            case 401:
                // Доступ запрещен - сразу в черный список
                $this->blacklist->add($domain, 'http_' . $httpCode, [
                    'url' => $url,
                    'error' => $error
                ]);
                break;
                
            case 404:
                // Не найдено - не баним сразу (может быть временно)
                writeLog("HTTP 404 for $domain, not blacklisting yet", 'parser');
                break;
                
            case 429:
                // Too Many Requests - баним, чтобы не долбить
                $this->blacklist->add($domain, 'http_429_rate_limit', [
                    'url' => $url,
                    'error' => $error
                ]);
                break;
                
            default:
                if ($httpCode >= 500) {
                    // Серверные ошибки - баним (сервер нестабильный)
                    $this->blacklist->add($domain, 'http_' . $httpCode . '_server_error', [
                        'url' => $url,
                        'error' => $error
                    ]);
                }
                break;
        }
    }
}
?>